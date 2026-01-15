<?php

declare(strict_types=1);

/**
 * Development notes:
 * 
 * The build algorithm is responsible for transforming assets, HTML files, 
 * Blade templates, and contents files (JSON and Markdown) located in the 
 * ./input direcctory into static files in the ./output directory.
 * 
 * Planned implementation steps:
 * 
 * 1. Copy assets and HTML files while preserving the directory structure
 *    and hierarchy (./input/pages -> ./output).
 * 
 * 2. Extend the algorithm to proccess Blade templates (.blade.php), redering 
 *    them into static HTML files (./input/pages -> ./output).
 * 
 * 3. Add ssupport for loading content files frrom ./input/content in JSON and 
 *    Markdown formats into memory, making them available to Blade templates 
 *    during rendering.
 */

try {
    $inputDirectory = __DIR__ . "/../../input";
    $outputDirectory = __DIR__ . "/../../output";

    cleanupDirectory($outputDirectory);
    processPages($inputDirectory, $outputDirectory);
} catch (\Throwable $th) {
    dd($th);
}



/**
 * Recursively removes all files and subdirectories inside a directory.
 * 
 * @param string $path Directory to be clean.
 * @param bool $removeSelf Wheter to remove the directory itself.
 * 
 * @return void
 */
function cleanupDirectory(string $path, bool $removeSelf = false): void
{
    if (!is_dir($path)) {
        return;
    }

    $itens = scandir($path);

    foreach ($itens as $item) {
        if ($item === '.' || $item === '..' || str_starts_with($item, '.')) {
            continue;
        }

        $fullPath = $path . DIRECTORY_SEPARATOR . $item;

        if (is_dir($fullPath)) {
            cleanupDirectory($fullPath, true);
            continue;
        }

        unlink($fullPath);
    }

    if ($removeSelf) {
        rmdir($path);
    }
}

/**
 * Recursively searches for files using glob based on the given pattern.
 * 
 * @param string $path Base directory to search.
 * @param string $pattern Glob pattern (deafult: '/*').
 * 
 * @return array<string> List of matched files paths.
 */
function recursiveGlob(string $path, string $pattern = '/*'): array
{
    $files = [];

    foreach (glob($path . $pattern, GLOB_NOSORT) as $item) {
        if (is_file($item)) {
            $files[] = $item;
            continue;
        }

        if (is_dir($item)) {
            $files = array_merge($files, recursiveGlob($item, $pattern));
        }
    }

    return $files;
}

/**
 * Process all page source files and writes the resulting output files.
 * 
 * The function scans the "pages" directory inside the input directory, 
 * preserves the directory structure, and delegates the processing of each file 
 * to `processPage`.
 * 
 * At this stage, files are copied as-is. Future processing steps (e.g. Blade
 * rendering, Markdown conversion) should be implemented inside `processPage`.
 *
 * @param  string $inputDirectory Base input directory.
 * @param  string $outputDirectory Base ouput directory.
 *
 * @return void
 */
function processPages(string $inputDirectory, string $outputDirectory)
{
    $pagesDirectory = rtrim($inputDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'pages';

    if (!is_dir($pagesDirectory)) {
        return;
    };

    $files = recursiveGlob($pagesDirectory);

    foreach ($files as $sourcePath) {
        if (!is_file($sourcePath)) {
            continue;
        }

        $targetPath = str_replace($pagesDirectory, $outputDirectory, $sourcePath);
        $targetDirectory = dirname($targetPath);

        if (!is_dir($targetDirectory)) {
            mkdir($targetDirectory, 0775, true);
        }

        processPage($sourcePath, $targetPath);
    }
}

/**
 * Processes a single page source file and writes the output file.
 * 
 * The processing strategy is determined by the file extension.
 * By default, files are copied without transformation.
 * 
 * This function is the extension point for future processors such as Blade 
 * templates, Markdown files, or other formats.
 *
 * @param  string $sourcePath
 * @param  string $targetPath
 *
 * @return void
 */
function processPage(string $sourcePath, string $targetPath): void
{
    $extension = pathinfo($sourcePath, PATHINFO_EXTENSION);

    match ($extension) {
        default => copy($sourcePath, $targetPath),
    };
}

/**
 * Dumpss the given values and terminates script execution.
 * 
 * Print all provided values followed by a simpliend baktrace pointing to the 
 * to the called of this function.
 * 
 * Intended for debugging purposes only.
 *
 * @param  mixed ...$values Values to be dumped.
 *
 * @return never
 */
function dd(mixed ...$values)
{
    echo "Values: ";
    print_r($values);
    echo PHP_EOL;

    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
    if (isset($backtrace[1])) {
        echo "Backtrace: ";
        print_r($backtrace[1]);
    }

    die();
}
