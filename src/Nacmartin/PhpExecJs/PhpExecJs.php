<?php

namespace Nacmartin\PhpExecJs;
use Symfony\Component\Process\Process;

class PhpExecJs
{
    /**
     * @var array
     */
    public $temporaryFiles = array();

    /**
     * @var string
     */
    public $context = null;

    public function __construct()
    {
        register_shutdown_function(array($this, 'removeTemporaryFiles'));
    }

    public function createContextFromFile($filename)
    {
        $this->createContext(file_get_contents($filename));
    }
    /**
     * Stores code as context
     *
     * @param $code string
     */
    public function createContext($code)
    {
        $this->context = $code;
    }

    /**
     * Evaluates JS code and returns the output
     *
     * @param $code string
     * @returns string
     */
    public function evalJs($code)
    {
        if ($this->context) {
            $code = $this->context."\n".$code;
        }
        $sourceFile = $this->createTemporaryFile($code, 'js');

        $binary = '/usr/bin/env node';
        $command = $binary;
        $escapedBinary = escapeshellarg($binary);
        if (is_executable($escapedBinary)) {
            $command = $escapedBinary;
        }
        $command .= ' '.$sourceFile;
        $process = new Process($command);
        $process->run();
        return $process->getOutput();
    }

    /**
     * Get TemporaryFolder
     * From Knp/Snappy (kudos)
     *
     * @return string
     */
    public function getTemporaryFolder()
    {
        return sys_get_temp_dir();
    }

    /**
     * Removes all temporary files
     */
    public function removeTemporaryFiles()
    {
        foreach ($this->temporaryFiles as $file) {
            $this->unlink($file);
        }
    }

    /**
     * Creates a temporary file.
     * The file is not created if the $content argument is null
     * From Knp/Snappy (kudos)
     *
     * @param string $content   Optional content for the temporary file
     * @param string $extension An optional extension for the filename
     *
     * @return string The filename
     *
     */
    protected function createTemporaryFile($content = null, $extension = null)
    {
        $dir = rtrim($this->getTemporaryFolder(), DIRECTORY_SEPARATOR);
        if (!is_dir($dir)) {
            if (false === @mkdir($dir, 0777, true) && !is_dir($dir)) {
                throw new \RuntimeException(sprintf("Unable to create directory: %s\n", $dir));
            }
        } elseif (!is_writable($dir)) {
            throw new \RuntimeException(sprintf("Unable to write in directory: %s\n", $dir));
        }
        $filename = $dir . DIRECTORY_SEPARATOR . uniqid('nacmartin_phpexecjs', true);
        if (null !== $extension) {
            $filename .= '.'.$extension;
        }
        if (null !== $content) {
            file_put_contents($filename, $content);
        }
        $this->temporaryFiles[] = $filename;
        return $filename;
    }

    public function __destruct()
    {
        $this->removeTemporaryFiles();
    }
}
