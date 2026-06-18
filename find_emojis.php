<?php
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('c:/xampp/htdocs/crochettei/app/Views'));
foreach ($files as $f) {
    if ($f->isFile() && $f->getExtension() === 'php') {
        $c = file_get_contents($f->getPathname());
        if (preg_match_all('/[\x{1F300}-\x{1F5FF}\x{1F600}-\x{1F64F}\x{1F680}-\x{1F6FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}\x{1F900}-\x{1F9FF}\x{1FA70}-\x{1FAFF}]/u', $c, $m)) {
            echo $f->getPathname() . ' -> ' . implode(' ', $m[0]) . "\n";
        }
    }
}
