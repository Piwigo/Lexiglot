<?php
// +-----------------------------------------------------------------------+
// | Lexiglot - A PHP based translation tool                               |
// +-----------------------------------------------------------------------+
// | Copyright(C) 2011-2013 Damien Sorel       http://www.strangeplanet.fr |
// +-----------------------------------------------------------------------+
// | This program is free software; you can redistribute it and/or modify  |
// | it under the terms of the GNU General Public License as published by  |
// | the Free Software Foundation                                          |
// |                                                                       |
// | This program is distributed in the hope that it will be useful, but   |
// | WITHOUT ANY WARRANTY; without even the implied warranty of            |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU      |
// | General Public License for more details.                              |
// |                                                                       |
// | You should have received a copy of the GNU General Public License     |
// | along with this program; if not, write to the Free Software           |
// | Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, |
// | USA.                                                                  |
// +-----------------------------------------------------------------------+

defined('LEXIGLOT_PATH') or die ('Hacking attempt!');

include(LEXIGLOT_PATH . 'include/smarty/Smarty.class.php');


class Template
{
  private $smarty;
  private $output = '';
  private $files = array();
  
  private $html_head_elements = array();
  private $html_style = '';
  private $scripts = array();
  private $footer_script = '';
  private $css_by_priority = array();
  
  
  function __construct()
  {
    // init Smarty
    $this->smarty = new Smarty();
    
    // default configuration
    $this->smarty->debugging = false;
    $this->smarty->compile_check = true;
    $this->smarty->force_compile = false;
    $this->smarty->template_dir = TEMPLATE_PATH;
    $this->smarty->compile_id = crc32(TEMPLATE_PATH.SALT_KEY);
    
    // compile directory
    $compile_dir = DATA_LOCATION . 'templates_c';
    mkgetdir($compile_dir);
    $this->smarty->compile_dir = $compile_dir;
    
    // custom blocks and functions
    $this->smarty->register_block('html_head', array(&$this, 'block_html_head') );
    $this->smarty->register_block('html_style', array(&$this, 'block_html_style') );
    $this->smarty->register_block('footer_script', array(&$this, 'block_footer_script') );
    
    $this->smarty->register_function('combine_script', array(&$this, 'func_combine_script') );
    $this->smarty->register_function('get_combined_scripts', array(&$this, 'func_get_combined_scripts') );
    $this->smarty->register_function('combine_css', array(&$this, 'func_combine_css') );
    $this->smarty->register_function('get_combined_css', array(&$this, 'func_get_combined_css') );
    
    $this->smarty->register_function('ui_message', array(&$this, 'func_ui_message') );
    $this->smarty->register_function('concat', array(&$this, 'func_concat') );
    $this->smarty->register_function('append', array(&$this, 'func_append') );
    
    $TemplateAdapter = new TemplateAdapter();
    $this->assign('lex', $TemplateAdapter);
  }
  
  /**
   * Deletes all compiled templates.
   */
  function delete_compiled_templates()
  {
    $save_compile_id = $this->smarty->compile_id;
    $this->smarty->compile_id = null;
    $this->smarty->clear_compiled_tpl();
    $this->smarty->compile_id = $save_compile_id;
  }
  
  /**
   * Sets the template filename for handle.
   */
  function set_filename($handle, $filename)
  {
    return $this->set_filenames(array($handle=> $filename));
  }

  /**
   * Sets the template filenames for handles. $filename_array should be a
   * hash of handle => filename pairs.
   */
  function set_filenames($filename_array)
  {
    if (!is_array($filename_array))
    {
      return false;
    }
    
    reset($filename_array);
    while(list($handle, $filename) = each($filename_array))
    {
      if (is_null($filename))
      {
        unset($this->files[$handle]);
      }
      else
      {
        $this->files[$handle] = $filename;
      }
    }
    return true;
  }
  
  /**
   * Assigns a value to template variable
   * http://www.smarty.net/manual/en/api.assign.php
   */
  function assign($tpl_var, $value=null)
  {
    $this->smarty->assign($tpl_var, $value);
  }
  
  /**
   * Inserts the compiled code for $handle as the value of $varname in the
   * root-level. This can be used to effectively include a template in the
   * middle of another template.
   * This is equivalent to assign($varname, $this->parse($handle, true))
   */
  function assign_var_from_handle($varname, $handle)
  {
    $this->assign($varname, $this->parse($handle, true));
  }
  
  /**
   * Appends a value to a template array
   * http://www.smarty.net/manual/en/api.append.php
   */
  function append($tpl_var, $value=null, $merge=false)
  {
    $this->smarty->append($tpl_var, $value, $merge);
  }
  
  /**
   * Appends a string to an existing variable assignment with the same name.
   */
  function concat($tpl_var, $value)
  {
    if (!empty($value))
    {
      $old_val = &$this->smarty->get_template_vars($tpl_var);
      if (isset($old_val))
      {
        $old_val.= $value;
      }
      else
      {
        $this->assign($tpl_var, $value);
      }
    }
  }
  
  /** 
   * Clears a variable assignement
   * http://www.smarty.net/manual/en/api.clear_assign.php
   */
  function clear_assign($tpl_var)
  {
    $this->smarty->clear_assign($tpl_var);
  }
  
  /** 
   * Gets a template variable
   * http://www.smarty.net/manual/en/api.get_template_vars.php
   */
  function &get_template_vars($name=null)
  {
    return $this->smarty->get_template_vars($name);
  }
  
  /**
   * Load the file for the handle, eventually compile the file and run the compiled
   * code. This will add the output to the results or return the result if $return
   * is true.
   */
  function parse($handle, $return=false)
  {
    if (!isset($this->files[$handle]))
    {
      $this->smarty->trigger_error("Template->parse(): Couldn't load template file for handle $handle", E_USER_ERROR);
    }

    // $this->smarty->assign('ROOT_URL', get_root_url());

    $v = $this->smarty->fetch($this->files[$handle], null, null, false);
    if ($return)
    {
      return $v;
    }
    else
    {
      $this->output.= $v;
    }
  }
  
  /**
   * Adds page header and sends the output to the browser
   */
  function flush()
  {     
    if (isset($this->files['header']))
    {
      $this->output = $this->parse('header', true) . $this->output;
    }
    if (isset($this->files['footer']))
    {
      $this->output.= $this->parse('footer');
    }
    
    if (count($this->html_head_elements) )
    {
      $search = "\n</head>";
      $pos = strpos($this->output, $search);
      if ($pos !== false)
      {
        $rep = "\n" . implode("\n", $this->html_head_elements);
        $this->output = substr_replace($this->output, $rep, $pos, 0);
      }
      $this->html_head_elements = array();
    }

    echo $this->output;
    $this->output = '';
  }
  
  /**
   * Prepares, parses end sends the page and exits the script
   */
  function close($handle)
  {
    include(LEXIGLOT_PATH . 'include/page_commons.inc.php');
    $this->set_filename($handle, $handle.'.tpl');
    $template->parse($handle);
    $template->flush();
    exit;
  }

  /**
   * Allows to add content just before </head> element in the output 
   * after the head has been parsed.
   */
  function block_html_head($params, $content)
  {
    $content = trim($content);
    if (!empty($content))
    {
      $this->html_head_elements[] = $content;
    }
  }

  /**
   * Allows to add <style> element in the output 
   * after the head has been parsed.
   */
  function block_html_style($params, $content)
  {
    $content = trim($content);
    if (!empty($content))
    {
      $this->html_style .= $content;
    }
  }
  
  /**
   * Includes a Javascript file in the current page.
   * param id - required
   * param path - required - the path to js file RELATIVE to piwigo root dir
   * param load - optional - header|footer, default header
   */
  function func_combine_script($params)
  {
    if (!isset($params['id']))
    {
      $this->smarty->trigger_error("combine_script: missing 'id' parameter", E_USER_ERROR);
    }
    if (!isset($params['path']))
    {
      $this->smarty->trigger_error("combine_script: missing 'path' parameter", E_USER_ERROR);
    }
    if (isset($params['load']))
    {
      if (!in_array($params['load'], array('footer','header')))
      {
        $this->smarty->trigger_error("combine_script: invalid 'load' parameter", E_USER_ERROR);
      }
    }
    else
    {
      $params['load'] = 'header';
    }
    
    $this->scripts[ $params['load'] ][ $params['id'] ] = $params['path'];
  }
  
  /**
   * Add inline Javascript to the page
   */
  function block_footer_script($params, $content)
  {
    $content = trim($content);
    if (!empty($content))
    {
      $this->footer_scripts.= $content . "\n";
    }
  }

  /**
   * Returns <script> tags for loaded Javascript files
   * param load - required - header|footer
   */
  function func_get_combined_scripts($params)
  {
    if (!isset($params['load']))
    {
      $this->smarty->trigger_error("get_combined_scripts: missing 'load' parameter", E_USER_ERROR);
    }
    
    $content = array();
    
    if (!empty($this->scripts[ $params['load'] ]))
    {
      foreach ($this->scripts[ $params['load'] ] as $id => $path)
      {
        $content[] = '<script type="text/javascript" src="'. $path . '"></script>';
      }
    }
    
    if ( $params['load'] == 'footer' and !empty($this->footer_scripts) )
    {
      $content[] = '<script type="text/javascript">' ."\n". $this->footer_scripts ."\n". '</script>';
    }

    return implode("\n", $content);
  }
  
  /**
   * Includes CSS stylesheet file in the current page.
   * param path - required - the path to CSS file RELATIVE to piwigo root dir
   */
  function func_combine_css($params)
  {
    if (empty($params['path']))
    {
      $this->smarty->trigger_error("func_combine_css: missing 'path' parameter", E_USER_ERROR);
    }
    if (empty($params['rank']))
    {
      $params['rank'] = 50;
    }
    
    $this->css_by_priority[ $params['rank'] ][] = $params['path'];
  }

  /**
   * Returns <link> tags for loaded CSS files
   */
  function func_get_combined_css($params)
  {
    $content = array();
    
    ksort($this->css_by_priority);
    foreach ($this->css_by_priority as $files)
    {
      foreach ($files as $path)
      {
        $content[] = '<link type="text/css" rel="stylesheet" media="all" href="'. $path .'">';
      }
    }
    
    if (strlen($this->html_style))
    {
      $content[] = '<style type="text/css">'.$this->html_style.'</style>';
      $this->html_style = '';
    }

    return implode("\n", $content);
  }
  
    
  /**
   * Displays a message with jQuery UI style
   * param content - required
   * param type - optional - default 'highlight'
   * param icon - optional - default 'bullet'
   * param style - optional - additional css
   */
  function func_ui_message($params)
  {
    if (!isset($params['content']))
    {
      $this->smarty->trigger_error("ui_message: missing 'content' parameter", E_USER_ERROR);
    }
    
    if (empty($params['type']))
    {
      $params['type'] = 'highligh';
    }
    if (empty($params['icon']))
    {
      $params['type'] = 'bullet';
    }
    
    return '
<div class="message ui-state-'. $params['type'] .'" '.(!empty($params['style'])?'style="'.$params['style'].'"':null).'>
  <span class="ui-icon ui-icon-'. $params['icon'] .'"></span>
  '. $params['content'] .'
</div>';
  }
  
  /**
   * Concatenates a variable
   * param var - required
   * param value - required
   */
  function func_concat($params)
  {
    if (!isset($params['var']))
    {
      $this->smarty->trigger_error("concat: missing 'var' parameter", E_USER_ERROR);
    }
    if (empty($params['value']))
    {
      return;
    }
    
    $this->concat($params['var'], $params['value']);
  }
  
  /**
   * Appends a value to a template array
   * param var - required
   * param value - required
   */
  function func_append($params)
  {
    if (!isset($params['var']))
    {
      $this->smarty->trigger_error("append: missing 'var' parameter", E_USER_ERROR);
    }
    if (empty($params['value']))
    {
      return;
    }
    
    $this->append($params['var'], $params['value']);
  }
}


/**
 * adapter for core functiosn access from the template
 */
class TemplateAdapter
{
  function language_name($lang, $force=false)
  {
    return get_language_name($lang, $force);
  }
  
  function language_flag($lang)
  {
    return get_language_flag($lang);
  }
  
  function language_url($lang)
  {
    return get_language_url($lang);
  }
  
  function language_rank($lang)
  {
    return get_language_rank($lang);
  }
  
  function project_name($proj)
  {
    return get_project_name($proj);
  }
  
  function project_url($proj)
  {
    return get_project_url($proj);
  }
  
  function project_rank($proj)
  {
    return get_project_rank($proj);
  }
  
  function username($user)
  {
    return get_username($user);
  }
  
  function user_status($user=null)
  {
    return get_user_status($user);
  }
  
  function user_url($user)
  {
    return get_user_url($user);
  }
  
  function is_admin()
  {
    return is_admin();
  }
  
  function is_manager()
  {
    return is_manager();
  }
}

?>