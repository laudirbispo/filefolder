<?php declare(strict_types=1);
namespace laudirbispo\FileAndFolder;
/**
 * Copyright (c) Laudir Bispo  (laudirbispo@outlook.com)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     (c) Laudir Bispo  (laudirbispo@outlook.com)
 * @since         2016
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

use DirectoryIterator;
use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Folder
{
	use TraitFileAndFolder;
	
	/**
     * Path to Folder.
     *
     * @var string
     */
	protected $path = '';
	
	/**
     * The chmmod for Linux platform
     *
     * @var int (octal)
     */
    const DEFAULT_MODE = 0644;
	
	public $errors = [];
	
	public $messages = [];
	
	public function __construct(?string $path = null)
    {
		$this->path = self::normalize($path);
    }
	
	/**
	 * Check if the current path exists
	 */
	public function exists() : bool
	{
		return (file_exists($this->path));
	}
	
	/**
	 * Returns true if the file exists and can be modified
	 */
	public function isWritable() : bool
	{
		if (!self::exists($this->path))
			return false;
		
		return (is_writable($this->path));
	}
	
	/**
	 * Create directorys
	 */
	public function create($mode = self::DEFAULT_MODE, bool $recursive = true) : bool
	{
		$path = self::normalize($this->path);
		
		if (self::exists($path))
			return true;

		$oldumask = umask(0);
		$dir = mkdir($path, $mode, $recursive); 
		umask($oldumask); 
		return $dir;
	}
	
	/**
     * Returns an array of nested directories and files in each directory
     *
     * @param string|null $path the directory path to build the tree from
     * @param array|bool $exceptions Either an array of files/folder to exclude
     *   or boolean true to not grab dot files/folders
     * @param string|null $type either 'file' or 'dir'. Null returns both files and directories
     * @return array Array of nested directories and files in each directory
     */
    public function tree($exceptions = false, $type = null)
    {
        if (!self::exists($this->path))
			return false;
		
        $files = [];
        $directories = [$this->path];

        if (is_array($exceptions)) {
            $exceptions = array_flip($exceptions);
        }
        $skipHidden = false;
        if ($exceptions === true) {
            $skipHidden = true;
        } elseif (isset($exceptions['.'])) {
            $skipHidden = true;
            unset($exceptions['.']);
        }

        try {
            $directory = new RecursiveDirectoryIterator($this->path, RecursiveDirectoryIterator::KEY_AS_PATHNAME | RecursiveDirectoryIterator::CURRENT_AS_SELF);
            $iterator = new RecursiveIteratorIterator($directory, RecursiveIteratorIterator::SELF_FIRST);
        } catch (Exception $e) {
            if ($type === null) {
                return [[], []];
            }

            return [];
        }

        foreach ($iterator as $itemPath => $fsIterator) {
            if ($skipHidden) {
                $subPathName = $fsIterator->getSubPathname();
                if ($subPathName{0} === '.' || strpos($subPathName, DIRECTORY_SEPARATOR . '.') !== false) {
                    continue;
                }
            }
            $item = $fsIterator->current();
            if (!empty($exceptions) && isset($exceptions[$item->getFilename()])) {
                continue;
            }

            if ($item->isFile()) {
                $files[] = $itemPath;
            } elseif ($item->isDir() && !$item->isDot()) {
                $directories[] = $itemPath;
            }
        }
        if ($type === null) {
            return [$directories, $files];
        }
        if ($type === 'dir') {
            return $directories;
        }

        return $files;
    }
	
	/**
     * Change the mode on a directory structure recursively. This includes changing the mode on files as well.
     *
     * @param string $path The path to chmod.
     * @param int|bool $mode Octal value, e.g. 0755.
     * @param bool $recursive Chmod recursively, set to false to only change the current directory.
     * @param array $exceptions Array of files, directories to skip.
     * @return bool Success.
     */
    public function setChmod($mode = self::DEFAULT_MODE, bool $recursive = true, array $exceptions = [])
    {
        if (!self::exists($this->path)){
			$this->errors[] = "[{$this->path}] - Não existe.";
			return false;
		}
		
		if (!self::isValidChmod($mode)){
			$this->messages[] = sprintf('O valor %s, é inválido para chmod. O valor padrão %s, foi aplicado para o diretório %s.', $mode, self::DEFAULT_MODE, $this->path);
		}

        if ($recursive === false && is_dir($this->path)) {
            //@codingStandardsIgnoreStart
            if (@chmod($this->path, intval($mode, 8))) {
                //@codingStandardsIgnoreEnd
                $this->messages[] = sprintf('%s alterado para %s', $this->path, $mode);

                return true;
            }
            $this->errors[] = sprintf('%s não alterado para %s', $this->path, $mode);
            return false;
        }

        if (is_dir($this->path)) {
            $paths = self::tree($this->path);

            foreach ($paths as $type) {
                foreach ($type as $fullpath) {
                    $check = explode(DIRECTORY_SEPARATOR, $fullpath);
                    $count = count($check);

                    if (in_array($check[$count - 1], $exceptions)) 
                        continue;
                    //@codingStandardsIgnoreStart
                    if (@chmod($fullpath, intval($mode, 8))) 
                        //@codingStandardsIgnoreEnd
                        $this->messages[] = sprintf('%s alterado para %s', $fullpath, $mode);
                    else 
                        $this->errors[] = sprintf('%s não alterado para %s', $fullpath, $mode);
                }
            }

            if (empty(self::$errors)) 
                return true;   
        }

        return false;
    }
	
	/**
     * Recursively Remove directories if the system allows.
     *
     * @param string|null $path Path of directory to delete
     * @return bool Success
     */
    public function delete()
    {

        if (is_dir($this->path)) {
            try {
                $directory = new RecursiveDirectoryIterator($this->path, RecursiveDirectoryIterator::CURRENT_AS_SELF);
                $iterator = new RecursiveIteratorIterator($directory, RecursiveIteratorIterator::CHILD_FIRST);
            } catch (\Exception $e) {
                return false;
            }

            foreach ($iterator as $item) {
                $filePath = $item->getPathname();
                if ($item->isFile() || $item->isLink()) {
                    //@codingStandardsIgnoreStart
                    if (@unlink($filePath)) {
                        //@codingStandardsIgnoreEnd
                        $this->messages[] = sprintf('%s removido.', $filePath);
                    } else {
                        $this->errors[] = sprintf('%s não removido.', $filePath);
                    }
                } elseif ($item->isDir() && !$item->isDot()) {
                    //@codingStandardsIgnoreStart
                    if (@rmdir($filePath)) {
                        //@codingStandardsIgnoreEnd
                        $this->messages[] = sprintf('%s removido.', $filePath);
                    } else {
                        $this->errors[] = sprintf('%s não removido', $filePath);
                        return false;
                    }
                }
            }

            $this->path = rtrim($this->path, DIRECTORY_SEPARATOR);
            //@codingStandardsIgnoreStart
            if (@rmdir($this->path)) {
                //@codingStandardsIgnoreEnd
                $this->messages[] = sprintf('%s removido', $this->path);
            } else {
                $this->errors[] = sprintf('%s não removido', $this->path);
                return false;
            }
        }

        return true;
    }
	
	
	/**
     * Returns $path with $element added, with correct slash in-between.
     *
     * @param string $path Path
     * @param string|array $element Element to add at end of path
     * @return string Combined path
     */
    public function addPathElement($element)
    {
        $element = (array)$element;
        array_unshift($element, rtrim($this->path, DIRECTORY_SEPARATOR));
        return implode(DIRECTORY_SEPARATOR, $element);
    }
	
	/**
     * Returns true if given $path is a Windows path.
     *
     * @param string $path Path to check
     * @return bool true if windows path, false otherwise
     */
    public static function isWindowsPath(string $path = '') : bool
    {
        return (preg_match('/^[A-Z]:\\\\/i', $path) || substr($path, 0, 2) === '\\\\');
    }

	public function hasErrors() : bool 
    {
        return (count($this->errors) > 1) ? true : false;
    }
    
    public function getErrors() : array 
    {
        return $this->errors;
    }
}
