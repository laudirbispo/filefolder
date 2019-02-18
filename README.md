## File and Folder handler

### Installation

Install the latest version with

```bash
$ composer require laudirbispo/filefolder
```

### Basic Usage

```php
<?php

use laudirbispo\FileAndFolder\{File, Folder};

 ### File class example
$File = new File('/site/index.php');
var_dump($File->exists());     
// return bool true

var_dump($File->extension());
// return string 'php' (length=3)

var_dump($File->dirname());
// return string 'C:/wamp64/www/emobi/site' (length=24)

var_dump($File->basename());
// return string 'index.php' (length=9)

var_dump($File->name());
// return string 'index' (length=5)

var_dump($File->size());
// return int 1432

var_dump($File->humanSize());
// return string '1,4 KB' (length=6)

var_dump($File->mimeType());
// return string 'text/x-php' (length=10)

var_dump($File->isValidMime());
// return boolean true

var_dump($File->isExecutable());
// return boolean false

var_dump($File->isReadable());
// return boolean true

var_dump($File->isWritable());
// return boolean true

var_dump($File->group());
// return Gets the file group. The group ID is returned in numerical format, use posix_getgrgid() to resolve it to a group name. 

var_dump($File->lastAccess());
// return int 1550083697

var_dump($File->lastChange());
// return int 1550083697

var_dump($File->owner());
// returns the user ID of the owner of the file, or FALSE on failure. The user ID is returned in numerical format, use posix_getpwuid() to resolve it to a username. 

var_dump($File->permissions());
// return octal chmod string '0666'

var_dump($File->setChmod(0755));
// return boolean true

var_dump($File->getInfo());
/** return array 
  'dirname' => string 'C:/wamp64/www/emobi/site' (length=24)
  'basename' => string 'index.php' (length=9)
  'extension' => string 'php' (length=3)
  'filename' => string 'index' (length=5)
  'mime_type' => string 'text/x-php' (length=10)
  'size' => int 1432
  'human_size' => string '1,4 KB' (length=6)
  'permissions' => string '0666' (length=4)
  'owner' => int 0
  'group' => int 0
  'last_access' => int 1550083697
*/

var_dump($File->setFile('other_file'));

var_dump($File->delete());
// return boolean true
 
 

```

## Creating a new file

```php
<?php

use libs\laudirbispo\FileAndFolder\{File, Folder};

$txtContent = "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Cras pulvinar nec elit in feugiat.  \n";
$textContent2 = "Etiam congue turpis sed blandit pulvinar.";

$File = new File('new_file.txt');

var_dump($File->create(0644));
// return boolean true

var_dump($File->open());
// return boolean true

$data = $File->read();
var_dump($data);
// return string ''

$txtContent = $File->prepare($txtContent, true);
// Prepares an ASCII string for writing. Converts line endings to the
// correct terminator for the current platform. If Windows, "\r\n" will be used,
// all other platforms will use "\n"

var_dump($File->write($txtContent));
// add content

var_dump($File->append($textContent2));
// adds content to the end of the pointer

$data = $File->read();
var_dump($data);
// return string 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Cras pulvinar nec elit in feugiat.  
// Etiam congue turpis sed blandit pulvinar.' (length=136)

var_dump($File->replaceText('Lorem ipsum dolor sit amet', '...replace text'));
// return boolean true

$data = $File->read();
var_dump($data);
// return string '...replace text, consectetur adipiscing elit. Cras pulvinar nec elit in feugiat.  
// Etiam congue turpis sed blandit pulvinar.' (length=125)

var_dump($File->clear());
// cleans the contents of the file

var_dump($File->close());
// close the file


```
## Working with folders example

```php
<?php

use libs\laudirbispo\FileAndFolder\{File, Folder};

use libs\laudirbispo\FileAndFolder\{File, Folder};

$Folder = new Folder('/teste');
var_dump($Folder->create());
// Create a new folder

var_dump($Folder->tree());
// Get an array containing the list of directories and files present

var_dump($Folder->setChmod(0755));
// return boolean

var_dump($Folder->exists());
// return boolean 

var_dump($Folder->isWritable());
// return boolean 

var_dump($Folder->isWritable());
// return boolean 

var_dump($Folder->delete());
// return boolean 

var_dump($Folder->hasErrors());
// return boolean 

var_dump($Folder->getErrors());
// return array

```

### Author

Laudir Bispo - <laudirbispo@outlook.com> - <https://twitter.com/laudir_bispo><br />

### License

FileAndFolder is licensed under the MIT License - see the `LICENSE` file for details
