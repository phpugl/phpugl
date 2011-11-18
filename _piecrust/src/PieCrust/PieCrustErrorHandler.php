<?php

namespace PieCrust;

use \Exception;
use PieCrust\Util\UriParser;


/**
 * A class that handles critical/fatal errors caught
 * while running a PieCrust application.
 */
class PieCrustErrorHandler
{
    /**
     * Formats an array of exceptions into an HTML chunk.
     */
    public static function formatErrors($errors, $printDetails = false)
    {
        $errorMessages = '<ul>';
        foreach ($errors as $e)
        {
            $errorMessages .= '<li><h3>' . $e->getMessage() . '</h3>';
            if ($printDetails)
            {
                $cur = $e;
                while ($cur != null)
                {
                    $errorMessages .= '<p>Message: <code>' . $cur->getMessage() . '</code></br>' .
                                      '   Error: <code>' . $cur->getCode() . '</code><br/>' .
                                      '   File: <code>' . $cur->getFile() . '</code><br/>' .
                                      '   Line <code>' . $cur->getLine() . '</code><br/>' .
                                      '   Trace: <code><pre>' . $cur->getTraceAsString() . '</pre></code></p>';
                    $cur = $cur->getPrevious();
                }
            }
            $errorMessages .= '</li>';
        }
        $errorMessages .= '</ul>';
        return $errorMessages;
    }
    
    protected $pieCrust;
    
    public function __construct(PieCrust $pieCrust)
    {
        $this->pieCrust = $pieCrust;
    }
    
    /**
     * Handles an exception by showing an appropriate
     * error page.
     */
    public function handleError(Exception $e)
    {
        $displayErrors = ((bool)ini_get('display_errors') or $this->pieCrust->isDebuggingEnabled());
        
        // If debugging is enabled, just display the error and exit.
        if ($displayErrors)
        {
            if ($e->getMessage() == '404')
            {
                //TODO: set header?
                piecrust_show_system_message('404');
                return;
            }
            $errorMessage = self::formatErrors(array($e), true);
            piecrust_show_system_message('error', $errorMessage);
            return;
        }
        
        // First of all, check that we're not running
        // some completely brand new and un-configured website.
        if ($this->isEmptySetup())
        {
            piecrust_show_system_message('welcome');
            return;
        }
        
        // Generic error message in case we don't have anything custom.
        $errorMessage = "<p>We're sorry but something very wrong happened, and we don't know what. We'll try to do better next time.</p>";
        
        // Get the URI to the custom error page.
        $errorPageUri = '_error';
        if ($e->getMessage() == '404')
        {
            header('HTTP/1.0 404 Not Found');
            $errorPageUri = '_404';
        }
        try
        {
            $errorPageUriInfo = UriParser::parseUri($this->pieCrust, $errorPageUri);
        }
        catch (Exception $inner)
        {
            // What the fuck.
            piecrust_show_system_message('critical', $errorMessage);
            return;
        }
        if ($errorPageUriInfo != null and is_file($errorPageUriInfo['path']))
        {
            // We have a custom error page. Show it, or display
            // the "fatal error" page if even this doesn't work.
            try
            {
                $this->pieCrust->runUnsafe($errorPageUri);
            }
            catch (Exception $inner)
            {
                // Well there's really something wrong.
                piecrust_show_system_message('critical', $errorMessage);
            }
        }
        else
        {
            // We don't have a custom error page. Just show a generic
            // error page and exit.
            piecrust_show_system_message(substr($errorPageUri, 1), $errorMessage);
        }
    }
    
    protected function isEmptySetup()
    {
        if (!is_dir($this->pieCrust->getRootDir() . PieCrust::CONTENT_DIR))
            return true;
        if (!is_file($this->pieCrust->getRootDir() . PieCrust::CONFIG_PATH))
            return true;
        
        return false;
    }
}
