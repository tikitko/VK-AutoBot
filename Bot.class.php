<?php

/*
 *      AutoBot V0.6
 *      PHP Bot for Vk
 *      By Nikita Bykov
 *
 */

class Bot
{
    const TITLE = 'AutoBot';
    const DESCRIPTION = 'PHP Bot for Vk';
    const AUTHOR = 'Nikita Bykov';
    const VERSION = '0.6';

    private static $AntiCaptcha_class_file = __DIR__ . '/AntiCaptcha.class.php';
    private static $VK_class_file = __DIR__ . '/VK.class.php';
    private static $commands_dir = __DIR__ . '/Commands/';
    private static $log_file = __DIR__ . '/Bot.log';

    private static $s = array(
        'o' => PHP_EOL,
        'e' => '',
        'p' => ' ',
        'd' => ' : ',
        's1' => '(',
        's2' => ')',
        'n' => 'NULL',
        'pp' => '.php',
        'vk_e' => 'VK class not loaded!',
        'ac_e' => 'AntiCaptcha not loaded!',
        'c_e' => 'Command file is not valid: ',
        'f_e' => 'Unable to open log file: ',
        'i_t' =>
            self::TITLE . ' V' . self::VERSION . PHP_EOL .
            self::DESCRIPTION . PHP_EOL .
            self::AUTHOR,
        'g_t' => 'Bot Launched!',
        'p_t' => 'Bot Disabled!',
        'e_h' => 'Error: ',
        'c_h' => 'Captcha: ',
        'm_h' => 'Method: ',
        'k_h' => 'Command: '
    );

    private $engine;
    private $vk;
    private $ac;
    private $commands;
    private $log;

    private $captcha_sid = '';
    private $captcha_key = '';

    public function __construct($vk_api_key, $ac_api_key = '')
    {
        $this->engine = true;
        if (is_file(self::$VK_class_file)) {
            require_once self::$VK_class_file;
        }
        if (class_exists('VK')) {
            $this->vk = new VK($vk_api_key);
        } else {
            die(self::$s['vk_e']);
        }
        if (!empty($ac_api_key)) {
            if (is_file(self::$AntiCaptcha_class_file)) {
                require_once self::$AntiCaptcha_class_file;
            }
            if (class_exists('AntiCaptcha')) {
                $this->ac = new AntiCaptcha($ac_api_key);
            } else {
                die(self::$s['ac_e']);
            }
        }
        $dir = scandir(self::$commands_dir);
        foreach ($dir as $value) {
            $path = self::$commands_dir . $value;
            if (is_file($path) && strpos($value, self::$s['pp'])) {
                $command = new class(self::$commands_dir . $value)
                {
                    private $command_class = NULL;

                    public function __construct($i)
                    {
                        include_once $i;
                        if (isset($command_class)) {
                            if (is_object($command_class) && method_exists($command_class, 'main')) {
                                $this->command_class = $command_class;
                            }
                            unset($command_class);
                        }
                    }

                    public function status()
                    {
                        if (isset($this->command_class) && is_object($this->command_class)) {
                            return true;
                        }
                        return false;
                    }

                    public function exec($a)
                    {
                        return ($this->command_class)->main($a);
                    }
                };
                if ($command->status()) {
                    $this->commands[str_replace(self::$s['pp'], self::$s['e'], $value)] = $command;
                } else {
                    die(self::$s['c_e'] . $value);
                }
                unset($command);
            }
        }
        $this->log = fopen(self::$log_file, 'a') or die(self::$s['f_e'] . self::$log_file);
    }

    public function __destruct()
    {
        unset($this->engine, $this->vk, $this->ac, $this->commands);
        if (is_resource($this->log)) {
            fclose($this->log);
        }
    }

    public function Date($format = 'Y-m-d H:i:s')
    {
        return date($format, time());
    }

    public function Logger($string, $add_date = true, $in_file = true)
    {
        if ($add_date) {
            $string = $this->Date() . self::$s['p'] . $string;
        }
        $l1 = $in_file ? fwrite($this->log, $string . self::$s['o']) : true;
        $l2 = print($string . self::$s['o']);
        return ($l1 && $l2);
    }

    public function Logger_Template($title, $d, ...$string)
    {
        $body = self::$s['s1'];
        foreach ($string as $value) {
            $body .= $value;
            if (next($string)) {
                $body .= $d ? self::$s['d'] : self::$s['e'];
            }
        }
        $body .= self::$s['s2'];
        $this->Logger($title . $body);
    }

    public function Requester($method, $parameters = array())
    {
        if (!empty($this->captcha_sid) && !empty($this->captcha_key)) {
            $parameters['captcha_sid'] = $this->captcha_sid;
            $parameters['captcha_key'] = $this->captcha_key;
            $this->captcha_sid = $this->captcha_key = self::$s['e'];
        }
        $response = $this->vk->api($method, $parameters);
        if (isset($response['response'])) {
            $la_methods = array(
                'messages.send',
                'account.setOnline',
                'account.setOffline'
            );
            if (array_search($method, $la_methods) !== false) {
                $this->Logger_Template(self::$s['m_h'], true, $method, $response['response']);
            }
        } elseif (isset($response['error'])) {
            $this->Logger_Template(self::$s['e_h'], true, $response['error']['error_msg'], $method);
            if (isset($response['error']['captcha_sid'])) {
                $captcha_code = $this->Captcha_Decoder($response['error']['captcha_img']);
                if (!empty($captcha_code)) {
                    $this->captcha_sid = $response['error']['captcha_sid'];
                    $this->captcha_key = $captcha_code;
                }
            }
        }
        return $response;
    }

    public function Message_Sender($message, $data)
    {
        $chat_id = isset($data['response'][1]['chat_id']) ? $data['response'][1]['chat_id'] : self::$s['e'];
        $user_id = empty($chat_id) ? $data['response'][1]['uid'] : self::$s['e'];
        return $this->Requester('messages.send', array(
            'user_id' => $user_id,
            'chat_id' => $chat_id,
            'message' => $message
        ));
    }

    private function Captcha_Decoder($captcha_img)
    {
        $return = self::$s['e'];
        if (isset($this->ac)) {
            $this->Logger_Template(self::$s['c_h'], true, $captcha_img);
            $ac_answer = $this->ac->run($captcha_img);
            $this->Logger_Template(self::$s['c_h'], true, $ac_answer['status'], $ac_answer['body']);
            if ($ac_answer['status'] == 'OK') {
                $return = $ac_answer['body'];
            }
        }
        return $return;
    }

    private function Command_Extractor($string, $deli = '#')
    {
        $key = $value = self::$s['e'];
        if (!empty($deli) && isset($string{0}) && $string{0} == $deli) {
            $parts = explode(self::$s['p'], $string, 2);
            $key = isset($parts[0]) ? substr($parts[0], 1) : self::$s['e'];
            $value = isset($parts[1]) ? $parts[1] : self::$s['e'];
        }
        return array('key' => $key, 'value' => $value);
    }

    private function Commands_Executor($command, $data = array())
    {
        $value = !empty($command['value']) ? $command['value'] : self::$s['n'];
        $this->Logger_Template(self::$s['k_h'], true, $command['key'], $value);
        switch ($command['key']) {
            case 'shutdown':
                $this->engine = false;
                $return = true;
                break;
            case 'info':
                $this->Message_Sender(self::$s['i_t'] . self::$s['o'] . $this->Date(), $data);
                $return = true;
                break;
            default:
                if (array_key_exists($command['key'], $this->commands) !== false) {
                    $command_answer = $this->commands[$command['key']]->exec(array($this, $data));
                    $return = boolval($command_answer);
                } else {
                    $return = false;
                }
                break;
        }
        if (!$return) {
            $this->Logger_Template(self::$s['e_h'], false, self::$s['k_h'], $command['key']);
        }
        return $return;
    }

    public function run()
    {
        $this->Logger(self::$s['o'] . self::$s['i_t'], false, false);
        $this->Logger(self::$s['o'] . self::$s['g_t'] . self::$s['o'], false, true);
        $sleep = 1;
        $uo_time = 780 / $sleep;
        for ($i = $uo_time, $rp_ms_id = 0; $this->engine; $i++) {
            if ($uo_time <= $i) {
                $online = $this->Requester('account.setOnline');
                if (isset($online['response']) && isset($online['response']) == 1) {
                    $i = 0;
                }
            }
            $ms = $this->Requester('messages.get', array('count' => 1, 'time_offset' => $sleep + 1));
            if (isset($ms['response'][1]['mid']) && $rp_ms_id != $ms['response'][1]['mid']) {
                $message_text = isset($ms['response'][1]['body']) ? $ms['response'][1]['body'] : self::$s['e'];
                $command = $this->Command_Extractor($message_text);
                if (!empty($command['key'])) {
                    $command['key'] = strtolower($command['key']);
                    $this->Commands_Executor($command, $ms);
                }
                $rp_ms_id = $ms['response'][1]['mid'];
            }
            sleep($sleep);
        }
        $this->Requester('account.setOffline');
        $this->Logger(self::$s['o'] . self::$s['p_t'] . self::$s['o'], false, true);
    }
}