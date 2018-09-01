<?php

require "libraries/TextTable.php";
require "GithubApi.php";
require "/home/jonasof/.composer/vendor/autoload.php";

# IT'S MEANT TO RUN ON UNIX SYSTEMS (input function fgets and shell_exec)

$githubApi = new GithubApi();
$githubApi->fetchCredencialsFromInput();

echo "Table info fetched in " . date("m/d/Y") . PHP_EOL . PHP_EOL;
$table = new TextTable([ "CMS", "PHP version *", "Stars", "Last Master Commit" ]);
$table->maxlen = 200;
echo $table->render(getCMSs($githubApi));
echo PHP_EOL . "* PHP version on master branch";

function getCMSs($githubApi) {
    $cmses = json_decode(file_get_contents(__DIR__ . "/../cms.json"));

    foreach ($cmses as $key=>$cms) {
        $cmses[$key]->repository_info = $githubApi->get("/repos/$cms->github");
        $cmses[$key]->composer = json_decode(base64_decode($githubApi->get("/repos/$cms->github/contents/composer.json")
            ->content));
        $cmses[$key]->commits = $githubApi->get("/repos/$cms->github/commits");
    }

    usort($cmses, function ($a, $b) {
        return ($a->repository_info->stargazers_count < $b->repository_info->stargazers_count);
    });

    return array_map(function($cms) {
        return [
            "[$cms->name](https://github.com/$cms->github)",
            $cms->composer->require->php,
            $cms->repository_info->stargazers_count,
            substr($cms->commits[0]->commit->author->date, 0, 10)
        ];
    }, $cmses);
}
