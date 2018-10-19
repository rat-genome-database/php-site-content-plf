This folder holds a the MyTable.php file to replace that found in the phpLiteFramework directory:

1) phpLiteFramework/trunk/plf/phpLiteFramework/util/table/MyTable.php

this should be a temporary fix until the code makes its way back into the main framework.

Alex Stoddard 28 Nov 2007

2) A bug fix (possibly only necessary on Solaris where we have an odd,
apparently implicit, int to floating point conversion).
Arguments passed to the make link function are now passed through 
urlencode(). This prevents a "+" in the string representation 
of a float from being interpreted as a space in the URL.

Alex Stoddard 30 Nov 2007