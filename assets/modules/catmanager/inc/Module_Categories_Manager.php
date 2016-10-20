<?php
/**
 * Class for MODx Categories Manager
 */
if( !is_object( $modx )
    || $modx->isBackend() === false )
{
    die('Please use the MODx Backend.');
}

require_once realpath( dirname(__FILE__) ) . DIRECTORY_SEPARATOR . 'Categories.php';

class Module_Categories_Manager extends Categories
{
    var $params           = array();
    var $translations     = array();
    var $new_translations = array();


    /**
     * Set a paramter key and its value
     * 
     * @return null 
     * @param string    $key    paramter key
     * @param mixed     $value  parameter value - could be mixed value-types
     */
    function set($key, $value)
    {
        $this->params[$key] = $value;
        return null;
    }


    /**
     * Get a parameter value
     * 
     * @return  mixed           return the parameter value if exists, otherwise false
     * @param   string  $key    Paramter-key
     */
    function get( $key )
    {
        global $modx;
        
        if( isset( $this->params[$key] ) )
        {
            return $this->params[$key];
        }
        elseif( isset( $modx->config[$key] ) )
        {
            return $modx->config[$key];
        }
        elseif( isset( $modx->event->params[$key] ) )
        {
            return $modx->event->params[$key];
        }
        return false;
    }

    
    function addMessage( $message, $namespace = 'default' )
    {
        $this->params['messages'][$namespace][] = $message;   
    }


    function getMessages( $namespace = 'default' )
    {
        if( isset( $this->params['messages'][$namespace] ) )
        {
            return $this->params['messages'][$namespace];
        }
        return false;
    }


    function renderView( $view_name, $data = array() )
    {
        $filename = trim( $view_name ) . '.tpl.phtml';
        $file     = self::get('views_dir') . $filename;
        $view     = & $this;

        if( is_file( $file ) 
            && is_readable( $file ) )
        {
            include $file;
        }
        else
        {
            echo sprintf(
                'View "%s<strong>%s</strong>" not found.',
                self::get('views_dir'), 
                $filename
            );
        }
    }

    function updateElement( $element, $element_id, $category_id )
    {
 
        $_update = array(
            'id'       => (int)$element_id,
            'category' => (int)$category_id
        );

        $this->db->update(
            $_update,
            $this->db_tbl[$element],
            "`id` = '" . (int)$element_id . "'"
        );

        if( $this->db->getAffectedRows() === 1 )
        {
            return true;
        }
        return false;
    }


    function txt( $txt )
    {
        return $txt;
        
        if( self::get('use_translator') === 'true'
            && is_file( self::get('_libdir') . 'Translator.php' ) )
        { 
            if( !is_object( $translator ) )
            {
                include self::get('_libdir') . 'Translator.php';
                $translator = new Translator('Module_Categories_Manager', self::get('manager_language') );
                $translator->setType('phrase');
            }
            return $translator->txt( $txt );
        }
        elseif( is_file( self::get('languages_dir') . $modx->config['manager_language'] . '.txt' ) )
        {
            $_txt = file( self::get('languages_dir') . self::get('manager_language') . '.txt' );
        }
        
    }

    function checkCategoriesDbTable()
    {
        $_columns = $this->db->getColumnNames(
            $this->db->select(
                '*',
                $this->db_tbl['categories'],
                '1 LIMIT 0,1'
            )
        );

        if( in_array('rank', $_columns, true ) )
        {
            return true;
        }
        return false;
    }


    function addColumnToCategoryDbTable()
    {
        $this->db->query("
            ALTER TABLE 
                " . $this->db_tbl['categories'] . "
            ADD 
                `rank` INT( 5 ) UNSIGNED NOT NULL DEFAULT '0'
            AFTER 
                `category`
        ");
        return true;
    }
}