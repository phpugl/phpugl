<?php

require_once 'src/PostCommand.php';

use PieCrust\PieCrustPlugin;

class PostPlugin extends PieCrustPlugin
{
    public function getName()
    {
        return "Post";
    }
    
    public function getCommands()
    {
      return array(
        new PostCommand(),
      ); 
    }
}
