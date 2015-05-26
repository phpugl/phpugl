<?php

//use \Exception;
//use \Console_CommandLine;
use PieCrust\Chef\Commands\ChefCommand;
use PieCrust\Chef\ChefContext;
use PieCrust\IPieCrust;

class PostCommand extends ChefCommand
{
    public function getName()
    {
        return 'post';
    }

    public function setupParser(Console_CommandLine $parser, IPieCrust $pieCrust)
    {
        $parser->description = 'Create a new post for blog.';
        $parser->addOption('vim', array(
            'short_name' => '-e',
            'long_name' => '--vim',
            'action' => 'StoreTrue',
            'description' => "Use vim to edit post",
            'default' => false,
            'help_name' => 'VIM',
        ));
        $parser->addOption('author', array(
            'short_name' => '-a',
            'long_name' => '--author',
            'action' => 'StoreArray',
            'description' => "Add author to post",
            'default' => null,
            'help_name' => 'NAME'
        ));
        $parser->addOption('date', array(
            'short_name' => '-d',
            'long_name' => '--date',
            'action' => 'StoreArray',
            'description' => "Give post a date string and time string (strtotime)",
            'default' => null,
            'help_name' => 'DATE'
        ));
        $parser->addOption('title', array(
            'short_name' => '-t',
            'long_name' => '--title',
            'action' => 'StoreArray',
            'description' => "Add title to posts",
            'default' => null,
            'help_name' => 'TITLE'
        ));
        $parser->addOption('tags', array(
            'long_name' => '--tags',
            'action' => 'StoreArray',
            'description' => "Add tags to posts",
            'default' => null,
            'help_name' => 'TAGLIST',
        ));
    }

    public function run(ChefContext $context)
    {
        $logger = $context->getLog();
        $pieCrust = $context->getApp();
        $result = $context->getResult();

        $timestamp = time();

        if ($result->command->options['title'] === NULL) {
          throw new Exception('Give a title to post');
        }

        $title = join(' ', $result->command->options['title']);
        $yamlFormatter = array(
          'title' => $title
        );

        if ($result->command->options['author']) {
          $yamlFormatter['author'] = join(' ', $result->command->options['author']);
        }

        if ($result->command->options['date']) {
          $timestamp = strtotime(join(' ', $result->command->options['date']));
        }
        $yamlFormatter['time'] = date('H:i:s', $timestamp);
        $yamlFormatter['updated'] = date('Y-m-d H:i:s', $timestamp);

        if ($result->command->options['tags']) {
          $yamlFormatter['tags'] = '[' . join(', ', $result->command->options['tags']) . ']';
        }

        $yamlFormatter['active'] = 'blog';

        $yamlFormatter['language'] = '[ de, en ]';

        $content = "---\n";
        foreach ($yamlFormatter as $k => $v) {
          $content .= sprintf("%s: %s\n", $k, $v);
        }
        $content .= "---\n\nHIER INHALT EINFÜGEN\n\n---en---\n\nPUT HERE YOUR CONTENT";

        $filename = sprintf("%s-%s_%s.html",
          date('m', $timestamp),
          date('d', $timestamp),
          $this->slugify($title)
        );

        $path = $pieCrust->getPostsDir() . date('Y', $timestamp) . DIRECTORY_SEPARATOR;

        if (!file_exists($path)) {
          mkdir($path);
        }

        file_put_contents($path . $filename, $content);
        $logger->info(sprintf("New post created at [%s]", $path . $filename));


        if ($result->command->options['vim']) {
            system('vim ' . $path . $filename . ' > `tty`');
        }
    }

    /**
     * Modifies a string to remove all non ASCII characters and spaces.
     */
    public function slugify($text)
    {
        // replace non letter or digits by -
        $text = preg_replace('~[^\\pL\d]+~u', '-', $text);

        // trim
        $text = trim($text, '-');

        // transliterate
        if (function_exists('iconv'))
        {
            $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        }

        // lowercase
        //$text = strtolower($text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        if (empty($text))
        {
            return 'n-a';
        }

        return $text;
    }
}
