<?php

require "libraries/TextTable.php";

# IS MEANT TO RUN ON UNIX SYSTEMS
# IF YOU WANT TO RUN ON WINDOWS, EDIT DIRECTLY $GITHUB_USER AND $GITHUB_PASSWORD

echo "Put yout github username:";
$GITHUB_USER = rtrim(fgets(STDIN));
echo "Put yout github password (not shown):";

$command = "/usr/bin/env bash -c 'read -s -p \""
      . "\" mypassword && echo \$mypassword'";
$GITHUB_PASSWORD = rtrim(shell_exec($command));

echo PHP_EOL . PHP_EOL;

echo "Table info fetched in " . date("m/d/Y") . PHP_EOL . PHP_EOL;
$table = new TextTable([ "CMS", "PHP version *", "Stars" ]);
$table->maxlen = 200;
echo $table->render(getCMSs());
echo PHP_EOL . "* PHP version on master branch";

function getCMSs() {

    $cmses = json_decode(file_get_contents(__DIR__ . "/../cms.json"));

    foreach ($cmses as $key=>$cms) {
        $cmses[$key]->repository_info = getGithub("/repos/$cms->github");
        $cmses[$key]->composer = json_decode(base64_decode(getGithub("/repos/$cms->github/contents/composer.json")
            ->content));
    }

    usort($cmses, function ($a, $b) {
        return ($a->repository_info->stargazers_count <
            $b->repository_info->stargazers_count);
    });

    $output = [];

    foreach ($cmses as $key=>$cms) {

        $output[] = [
            "[$cms->name](https://github.com/$cms->github)",
            $cms->composer->require->php,
            $cms->repository_info->stargazers_count
        ];

    }

    return $output;
}

function getGithub($resource = "/repos") {

    global $GITHUB_USER, $GITHUB_PASSWORD;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.github.com" . $resource);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, "$GITHUB_USER:$GITHUB_PASSWORD");
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
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
