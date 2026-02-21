<?php
setcookie("testcookie", "hello", time()+3600, "/");
echo "Cookie set. Reload this page.";
