<?php

// if this function returns anything, it is displayed without executing the
// requested module and function
// if it doesn't return anything, the system will proceed and call the requested
// module and function
// if this file doesn't exist at all, the system doesn't care, and it calls the
// requested module and function
// this is useful for implementing security across an entire module, for example
function pre($module, $func) {
  return null;
}
?>
