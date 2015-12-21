<?php

$dir = $argc > 1 ? $argv[1] : __DIR__;
chdir($dir);

$exec = function ($command) use ($dir) {
    exec($command, $output);

    return $output;
};

$files = $exec('git ls-files');

$globalStats = [];
$maxAuthorNameLength = mb_strlen('Имя автора');
$maxCountLength = mb_strlen('Счёт');

$loaderLength = 0;
$totalLines = 0;
$countFiles = count($files);

foreach ($files as $key => $file) {
    $stats = $exec('git blame --line-porcelain '.$file.' 2> /dev/null | sed -n \'s/^author //p\' | sort | uniq -c | sort -rn ');
    foreach ($stats as $line) {
        list($count, $author) = explode(' ', trim($line), 2);
        $globalStats[$author] = isset($globalStats[$author]) ? $globalStats[$author] : 0;
        $globalStats[$author] += (int)$count;

        $authorNameLength = mb_strlen($author, mb_detect_encoding($author));
        $countLength = strlen((string)$count);
        if ($authorNameLength > $maxAuthorNameLength) {
            $maxAuthorNameLength = $authorNameLength;
        }

        if ($countLength > $maxCountLength) {
            $maxCountLength = $countLength;
        }

        $totalLines += $count;
        $countLength = strlen((string)$totalLines);
        if ($countLength > $maxCountLength) {
            $maxCountLength = $countLength;
        }

        unset($countLength, $authorNameLength, $line);
    }

    if ($loaderLength) {
        echo str_repeat(' ', $loaderLength)."\r";
    }
    $loader = ("    ".($key+1)."/$countFiles --- $file");
    echo $loader."\r";
    $loaderLength = mb_strlen($loader, mb_detect_encoding($loader));
}

if ($loaderLength) {
    echo str_repeat(' ', $loaderLength)."\r";
}

uasort($globalStats, function ($a, $b) {
    if ($a === $b) return 0;

    return $a < $b ? 1 : -1;
});

$spaces = function ($str, $need) {
    $spacesCount = $need - mb_strlen((string)$str, mb_detect_encoding((string)$str));
    if ($spacesCount > 0) {
        $str .= str_repeat(' ', $spacesCount);
    }
    return $str;
};


print '┌'.str_repeat('─', 2 + $maxAuthorNameLength).'┬'.str_repeat('─', 2 + $maxCountLength)."┐\n";
printf("│ %s │ %s │\n", $spaces('Имя автора', $maxAuthorNameLength), $spaces('Счёт', $maxCountLength));
print '├'.str_repeat('─', 2 + $maxAuthorNameLength).'┼'.str_repeat('─', 2 + $maxCountLength)."┤\n";

$top5 = count($globalStats) > 5;
$counter = 0;
foreach ($globalStats as $author => $count) {
    printf("│ %s │ %s │\n", $spaces($author, $maxAuthorNameLength), $spaces($count, $maxCountLength));
    $counter++;

    if ($top5 && $counter === 5) {
        print '├'.str_repeat('─', 2 + $maxAuthorNameLength).'┼'.str_repeat('─', 2 + $maxCountLength)."┤\n";
    }

}
print '├'.str_repeat('─', 2 + $maxAuthorNameLength).'┴'.str_repeat('─', 2 + $maxCountLength)."┤\n";

printf("│ %s   %s │\n", $spaces('', $maxAuthorNameLength), $spaces($totalLines, $maxCountLength));

print '└'.str_repeat('─', 5 + $maxAuthorNameLength + $maxCountLength)."┘\n";

