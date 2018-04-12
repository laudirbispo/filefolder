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
 * @since         1.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

use finfo;

class File
{
	use TraitFileAndFolder;
	
	/**
	 * The file
	 *
	 * var (string)
	 */
	protected $file;
	
	/**
	 * The file name
	 *
	 * var (string)
	 */
	protected $name;
	
	/**
     * Holds the file handler resource if the file is opened
     *
     * @var resource|null
     */
    public $handle;
	
	/**
     * Enable locking for file reading and writing
     *
     * @var bool|null
     */
    public $lock;
	
	/**
	 * Prefix to file names
	 *
	 * @var (string)
	 */
	protected $prefix = '';
	
	/**
	 * The valid list of extensions and mime types
	 */
	protected $mime_types = [];
	
	public $errors = [];
	
	public function __construct (string $file = '')
	{
		$this->file = self::normalize($file);
		if (empty($this->mime_types))
			$this->mime_types = include('mime_types.php');
	}
	
	/**
	 * Sets a new file for analize
	 */
	public function setFile (string $file = '')
	{
		$this->file = self::normalize($file);
		return $this;
	}
	
	/**
	 * check if file exists
	 */
	public function exists () : bool
	{
		return (file_exists($this->file) && is_file($this->file));
	}
	
	/**
	 * Return the file extension
	 *
	 * @return (mixed) - The value or NULL
	 */
	public function extension ()
	{
		return pathinfo($this->file, PATHINFO_EXTENSION);
	}
	
	/**
	 * Return the Dirname (folder path)
	 *
	 * @return (mixed) - The value or NULL
	 */
	public function dirname ()
	{
		return pathinfo($this->file, PATHINFO_DIRNAME);
	}
	
	/**
	 * Returns the file name with the extension
	 *
	 * @return (mixed) - The value or NULL
	 */
	public function basename ()
	{
		return pathinfo($this->file, PATHINFO_BASENAME);
	}
	
	/**
	 * Returns the file name without the extension
	 *
	 * @return (mixed) - The value or NULL
	 */
	public function name ()
	{
		return pathinfo($this->file, PATHINFO_FILENAME);
	}
	
	/**
	 * Returns the human readable mode
	 *
	 * @return (mixed) - The value or NULL
	 */
	public function humanSize ()
	{
		return self::convertSizeToHumans($this->size());
	}
	
	/**
	 * Set the prefix name
	 *
	 * @return (mixed) - The value or NULL
	 */
	public function setPrefix (string $prefix = '')
	{
		$this->prefix = $prefix;
		return $this;
	}
	
	/**
	 * Get the prefix 
	 *
	 * @return (mixed) - The value or NULL
	 */
	public function getPrefix ()
	{
		return $this->prefix;
	}
	
	/**
     * Returns true if the File is executable.
     *
     * @return bool True if it's executable, false otherwise
     */
    public function isExecutable () : bool
    {
        return is_executable($this->file);
    }

    /**
     * Returns true if the file is readable.
     *
     * @return bool True if file is readable, false otherwise
     */
    public function isReadable () : bool
    {
        return is_readable($this->file);
    }
	
	/**
	 * Returns true if the file exists and can be modified
	 */
	public function isWritable () : bool
	{
		return is_writable($this->getDirname());
	}

	
	/**
	 * Get file informations
	 */
	public function getInfo ()
	{
		if (!$this->exists())
		{
			$this->errors[] = sprintf("O arquivo [%s] não existe.", $this->file);
			//return $info[] = '';
		}
		
		$info = pathinfo($this->file);
		$info['mime_type'] = $this->mimeType();
		$info['size'] = $this->size();
		$info['human_size'] = self::convertSizeToHumans($this->size());
		$info['permissions'] = $this->permissions();
		$info['owner'] = $this->owner();
		$info['group'] = $this->group();
		$info['last_access'] = $this->lastAccess();
		$info['last_change'] = $this->lastChange();
		return $info;
	}
	
	/**
	 * Returns the file mime type
	 *
	 * @return (mixed) - The value or NULL
	 */
	public function mimeType ()
	{
		if ($this->exists()) 
			return mime_content_type($this->file);
		return null;
	}
	
	/**
     * Returns the file's group.
     *
     * @return string|null The file group, or null in case of an error
     */
    public function group ()
    {
        if ($this->exists()) 
            return filegroup($this->file);
        return null;
    }

    /**
     * Returns last access time.
     *
     * @return int|null Timestamp of last access time, or null in case of an error
     */
    public function lastAccess ()
    {
        if ($this->exists()) 
            return fileatime($this->file);
        return null;
    }

    /**
     * Returns last modified time.
     *
     * @return int|null Timestamp of last modification, or null in case of an error
     */
    public function lastChange ()
    {
        if ($this->exists()) 
            return filemtime($this->file);
        return null;
    }
	
	 /**
     * Returns the file's owner.
     *
     * @return string|null The file owner, or null in case of an error
     */
    public function owner ()
    {
        if ($this->exists()) 
            return fileowner($this->file);
        return null;
    }
	
	/**
     * Returns the "chmod" (permissions) of the file.
     *
     * @return string|false Permissions for the file, or false in case of an error
     */
    public function permissions ()
    {
        if ($this->exists()) 
            return substr(sprintf('%o', fileperms($this->file)), -4);
        return null;
    }
	
	
	/**
	 * Returns the full size of file
	 */
	public function size () 
	{
		if (!$this->exists())
		{
			$size = null;
		}
		else if (!(strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')) 
		{
			$size = trim(`stat -c%s $this->file`);
		}
		else
		{
			$fsobj = new \COM("Scripting.FileSystemObject");
			$f = $fsobj->GetFile($this->file);
			$size = $f->Size;
		}

        return $size;
    } 
	
	/**
	 * Rename or move the file
	 *
	 * @param $new_name (mixed) - case null, automatically generates the name
	 */
	public function rename ($new_name = null)
	{
	
		if (!$this->exists())
		{
			$this->erros[] = sprintf("Não foi possível renomear o arquivo [%s] porque ele não existe!", $this->file);
			return false;
 		}
		$old_name = $this->file;
		if (null === $new_name)
			$new_name = self::generateRandomName();
		
		$new_path = $this->dirname() . DIRECTORY_SEPARATOR . $this->prefix . $new_name . '.' . $this->extension();
		$new_path =  self::normalize($new_path);
		$rename = rename($old_name, $new_path);
		if ($rename && null === $new_name) 
		{
			$this->setFile($new_path);
			return $this->prefix . $new_name . '.' . $this->extension();
		}
		else if ($rename && null !== $new_name) 
		{
			$this->setFile($new_path);
			return true;
		}

		return false;	
	}
	
	/**
	 * Checks whether the file is valid, according to the extension and MIME type
	 */
	public function isValidMime () : bool
	{
		$ext = $this->extension();
		$mime = $this->mimeType();
		
		if (!empty($this->mime_types))
		{
			if (array_key_exists($ext, $this->mime_types))
			{
				$exts = array_keys($this->mime_types, $mime);
				return (in_array($ext, $exts)); 	    
			}
			
			return false;
		}
		$this->errors[] = "Não foi possível verificar a integridade do arquivo. A lista de extensões e mimes não foi carregada!";
		return false;
	}
	
	/** 
	 * Creates a new file
	 */
	public function create()
	{
		if (!$this->exists())
			fopen($this->file, "a");
		
		if (touch($this->file)) 
            return true;
		else
			return true;
	}
	
	/**
     * Opens the current file with a given $mode
     *
     * @param string $mode A valid 'fopen' mode string (r|w|a ...)
     * @param bool $force If true then the file will be re-opened even if its already opened, otherwise it won't
     * @return bool True on success, false on failure
     */
    public function open($mode = 'a', $force = false)
    {
        if (!$force && is_resource($this->handle)) 
            return true;

        if ($this->exists() === false) 
            return false;

        $this->handle = fopen($this->file, $mode);
        return is_resource($this->handle);
    }

    /**
     * Return the contents of this file as a string.
     *
     * @param string|bool $bytes where to start
     * @param string $mode A `fread` compatible mode.
     * @param bool $force If true then the file will be re-opened even if its already opened, otherwise it won't
     * @return string|false string on success, false on failure
     */
    public function read($bytes = false, $mode = 'rb', $force = false)
    {
        if ($bytes === false && $this->lock === null) 
            return file_get_contents($this->file);
     
        if ($this->open($mode, $force) === false) 
            return false;
     
        if ($this->lock !== null && flock($this->handle, LOCK_SH) === false) 
            return false;
        
        if (is_int($bytes)) 
            return fread($this->handle, $bytes);

        $data = '';
        while (!feof($this->handle)) 
		{
            $data .= fgets($this->handle, 4096);
        }

        if ($this->lock !== null) 
            flock($this->handle, LOCK_UN);

        if ($bytes === false) 
            $this->close();

        return trim($data);
    }

    /**
     * Sets or gets the offset for the currently opened file.
     *
     * @param int|bool $offset The $offset in bytes to seek. If set to false then the current offset is returned.
     * @param int $seek PHP Constant SEEK_SET | SEEK_CUR | SEEK_END determining what the $offset is relative to
     * @return int|bool True on success, false on failure (set mode), false on failure or integer offset on success (get mode)
     */
    public function offset ($offset = false, $seek = SEEK_SET)
    {
        if ($offset === false) 
		{
            if (is_resource($this->handle)) 
                return ftell($this->handle);
        } 
		elseif ($this->open() === true) 
		{
            return fseek($this->handle, $offset, $seek) === 0;
        }

        return false;
    }

    /**
     * Write given data to this file.
     *
     * @param string $data Data to write to this File.
     * @param string $mode Mode of writing. {@link https://secure.php.net/fwrite See fwrite()}.
     * @param bool $force Force the file to open
     * @return bool Success
     */
    public function write ($data, $mode = 'w', $force = false)
    {
        $success = false;
        if ($this->open($mode, $force) === true) 
		{
            if ($this->lock !== null && flock($this->handle, LOCK_EX) === false) 
                return false;

            if (fwrite($this->handle, $data) !== false) 
                $success = true;

            if ($this->lock !== null) 
                flock($this->handle, LOCK_UN);
        }

        return $success;
    }

    /**
     * Closes the current file if it is opened.
     *
     * @return bool True if closing was successful or file was already closed, otherwise false
     */
    public function close ()
    {
        if (!is_resource($this->handle)) {
            return true;
        }
        return fclose($this->handle);
    }
	
	/**
     * Append given data string to this file.
     *
     * @param string $data Data to write
     * @param bool $force Force the file to open
     * @return bool Success
     */
    public function append ($data, $force = false)
    {
        return $this->write($data, 'a', $force);
    }
	
	/**
     * Searches for a given text and replaces the text if found.
     *
     * @param string|array $search Text(s) to search for.
     * @param string|array $replace Text(s) to replace with.
     * @return bool Success
     */
    public function replaceText ($search, $replace)
    {
        if (!$this->open('r+')) 
            return false;

        if ($this->lock !== null && flock($this->handle, LOCK_EX) === false) 
            return false;

        $replaced = $this->write(str_replace($search, $replace, $this->read()), 'w', true);

        if ($this->lock !== null) 
            flock($this->handle, LOCK_UN);

        $this->close();
        return $replaced;
    }
	
	/**
     * Prepares an ASCII string for writing. Converts line endings to the
     * correct terminator for the current platform. If Windows, "\r\n" will be used,
     * all other platforms will use "\n"
     *
     * @param string $data Data to prepare for writing.
     * @param bool $force_windows If true forces Windows new line string.
     * @return string The with converted line endings.
     */
    public static function prepare ($data, $force_windows = false)
    {
        $lineBreak = "\n";
        if (DIRECTORY_SEPARATOR === '\\' || $force_windows === true) 
            $lineBreak = "\r\n";

        return strtr($data, ["\r\n" => $lineBreak, "\n" => $lineBreak, "\r" => $lineBreak]);
    }
	
	/**
	 * Clear contents of file
	 */
	public function clear ()
	{
		return $this->write('');
	}
	
	/**
     * Deletes the file.
     *
     * @return bool 
     */
    public function delete ()
    {
        if (is_resource($this->handle)) 
		{
            fclose($this->handle);
            $this->handle = null;
        }
        if ($this->exists()) 
            return unlink($this->file);
        
        return false;
    }
	
}
