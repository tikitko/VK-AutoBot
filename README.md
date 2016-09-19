# VK AutoBot
#### Bot for [VKontakte](https://vk.com/) written in PHP!
At the moment, the bot monitors all sent messages to him and responds to them if they are command.
The main advantage of this system is a bot command-module, which allows you to create custom commands to the bot will perform ...

### How to use
  1. You need clone repo.
  2. Open the file ```launcher.php``` and install API keys for VK and AntiCaptcha (Recently optionally).

    ```php
    #!/bin/bash
    <?php
    require_once 'Bot.class.php';
    $bot = new Bot(
        'HERE', // VK API KEY
        'AND HERE' // ANTI-CAPTCHA API KEY (https://rucaptcha.com/)
    );
    $bot->run();
    ```  

  3. Run like this:

    ``` sh
    $ php ./launcher.php
    ```
    or
    ``` sh
    $ chmod +x ./launcher.php
    $ ./launcher.php
    ```
    
### How to write the command
Detailed description of the item can be found in the file ```example.php```.

### Contact bns.6587@gmail.com for all questions