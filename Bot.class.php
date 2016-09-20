<?php

/*
 *      AutoBot V0.7.1
 *      PHP Bot for Vk
 *      By Nikita Bykov
 *
 */

class Bot
{
    const TITLE = 'AutoBot';
    const DESCRIPTION = 'PHP Bot for VK';
    const AUTHOR = 'Nikita Bykov';
    const VERSION = '0.7.1';

    const SLEEP = 1;
    const C_SEPARATOR = '#';

    private static $AntiCaptcha_class_file = __DIR__ . '/AntiCaptcha.class.php';
    private static $VK_class_file = __DIR__ . '/VK.class.php';
    private static $commands_dir = __DIR__ . '/Commands/';
    private static $log_file = __DIR__ . '/Bot.log';

    private static $s = array(
        'e' => '',
        'p' => ' ',
        'd' => ' : ',
        't' => '; ',
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
            self::AUTHOR . PHP_EOL,
        'g_t' => 'Bot Launched!',
        'p_t' => 'Bot Disabled!',
        'l_h' => 'Commands list: ',
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

    public function __construct($vk_api_key, $ac_api_key = NULL)
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
        $this->commands = array();
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

                    public function text()
                    {
                        $title = ($this->command_class)::TITLE;
                        $description = ($this->command_class)::DESCRIPTION;
                        if ($title !== NULL && $description !== NULL) {
                            $return = array($title, $description);
                        } else {
                            $return = array();
                        }
                        return $return;
                    }

                    public function exec($a)
                    {
                        return ($this->command_class)->main($a);
                    }
                };
                if ($command->status()) {
                    $command_name = strtolower(str_replace(self::$s['pp'], self::$s['e'], $value));
                    $this->commands[$command_name] = $command;
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
        $l1 = $in_file ? fwrite($this->log, $string . PHP_EOL) : true;
        $l2 = print($string . PHP_EOL);
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

    public function Requester($method, $parameters = array(), $attempts_amount = 3)
    {
        $response = $captcha_data = array();
        $flood_control = false;
        for ($i = 0, $j = $attempts_amount; $i >= 0; $i--) {
            if (!empty($captcha_data[0]) && !empty($captcha_data[1])) {
                $parameters['captcha_sid'] = $captcha_data[0];
                $parameters['captcha_key'] = $captcha_data[1];
                $captcha_data = array();
            }
            if ($flood_control) {
                $date = self::$s['p'] . self::$s['s1'] . $this->Date() . self::$s['s2'];
                $parameters['message'] .= $date;
                $flood_control = false;
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
                $error_code = $response['error']['error_code'];
                $error_msg = $response['error']['error_msg'];
                $this->Logger_Template(self::$s['e_h'], true, $error_code, $error_msg, $method);
                switch ($response['error']['error_code']) {
                    case 14:
                        $captcha_code = $this->Captcha_Decoder($response['error']['captcha_img']);
                        if (!empty($captcha_code)) {
                            $captcha_data[0] = $response['error']['captcha_sid'];
                            $captcha_data[1] = $captcha_code;
                        }
                        break;
                    case 9:
                        $flood_control = true;
                        break;
                }
                if ($j > 1) {
                    $j--;
                    $i++;
                }
            }
            if ($attempts_amount != $j) {
                sleep(self::SLEEP);
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

    private function Command_Extractor($string)
    {
        $key = $value = self::$s['e'];
        if (!empty(self::C_SEPARATOR) && isset($string{0}) && $string{0} == self::C_SEPARATOR) {
            $parts = explode(self::$s['p'], $string, 2);
            $key = isset($parts[0]) ? substr($parts[0], 1) : self::$s['e'];
            $value = isset($parts[1]) ? $parts[1] : self::$s['e'];
        }
        return array($key, $value);
    }

    private function Commands_Executor($command, $data = array())
    {
        $value = !empty($command[1]) ? $command[1] : self::$s['n'];
        $this->Logger_Template(self::$s['k_h'], true, $command[0], $value);
        switch ($command[0]) {
            case 'shutdown':
                $this->engine = false;
                $return = true;
                break;
            case 'info':
                $this->Message_Sender(self::$s['i_t'], $data);
                $return = true;
                break;
            case 'help':
                $massage = self::$s['l_h'];
                foreach ($this->commands as $key => $val) {
                    $c_text = ($this->commands[$key])->text();
                    if ($c_text != array()) {
                        $about = self::$s['s1'] . $c_text[0] . self::$s['p'] . $c_text[1] . self::$s['s2'];
                    } else {
                        $about = self::$s['n'];
                    }
                    $massage .= self::C_SEPARATOR . $key . self::$s['p'] . $about . self::$s['t'];
                }
                $this->Message_Sender($massage, $data);
                $return = true;
                break;
            default:
                if (array_key_exists($command[0], $this->commands) !== false) {
                    $command_answer = $this->commands[$command[0]]->exec(array($this, $command, $data));
                    $return = boolval($command_answer);
                } else {
                    $return = false;
                }
                break;
        }
        if (!$return) {
            $this->Logger_Template(self::$s['e_h'], false, self::$s['k_h'], $command[0]);
        }
        return $return;
    }

    public function run()
    {
        $this->Logger(self::$s['i_t'], false, false);
        $this->Logger(self::$s['g_t'], false, true);
        $this->Requester('account.setOnline', array(), 5);
        $uo_time = 600 / self::SLEEP;
        for ($i = 0, $rp_ms_id = 0; $this->engine; $i++) {
            if ($uo_time <= $i) {
                $this->Requester('account.setOnline', array(), 5);
                $i = 0;
            }
            $ms = $this->Requester('messages.get', array(
                'count' => 1,
                'time_offset' => self::SLEEP + 1
            ));
            if (isset($ms['response'][1]['mid']) && $rp_ms_id != $ms['response'][1]['mid']) {
                $message_text = isset($ms['response'][1]['body']) ? $ms['response'][1]['body'] : self::$s['e'];
                $command = $this->Command_Extractor($message_text);
                if (!empty($command[0])) {
                    $command[0] = strtolower($command[0]);
                    $this->Commands_Executor($command, $ms);
                }
                $rp_ms_id = $ms['response'][1]['mid'];
            }
            sleep(self::SLEEP);
        }
        $this->Requester('account.setOffline', array(), 5);
        $this->Logger(self::$s['p_t'], false, true);
    }
}