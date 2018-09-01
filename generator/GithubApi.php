<?php

class GithubApi
{
    const AUTH_USER_PASS = 1;
    const AUTH_TOKEN = 2;

    public $auth_method;

    public $user;
    public $password;

    public $token;

    function fetchCredencialsFromInput ()
    {
        echo "Autorization method: (1: User and Pass, 2: Token)";
        $this->auth_method = rtrim(fgets(STDIN));

        if ($this->auth_method == self::AUTH_USER_PASS) {
            echo "Put yout github username:";
            $this->user = rtrim(fgets(STDIN));
            echo "Put yout github password (not shown):";

            $command = "/usr/bin/env bash -c 'read -s -p \""
                  . "\" mypassword && echo \$mypassword'";
            $this->pass = rtrim(shell_exec($command));
            echo PHP_EOL . PHP_EOL;
        } else {
            echo "Put yout github token:";
            $this->token = rtrim(fgets(STDIN));
        }
    }

    function get($resource = "/repos")
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.github.com" . $resource);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($this->auth_method == self::AUTH_USER_PASS) {
          curl_setopt($ch, CURLOPT_USERPWD, "$this->user:$this->pass");
          curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        } else {
          curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: token $this->token"]);
        }
        curl_setopt($ch, CURLOPT_USERAGENT,'Laravel CMS List');
        $output = curl_exec($ch);

        if(curl_errno($ch)){
            die('Request Error:' . curl_error($ch));
        }

        $info = curl_getinfo($ch);
        curl_close($ch);

        if ($info['http_code']  == 401) die("Bad Credentials");

        return json_decode($output);
    }
}
