<?php
namespace adapt\sql_install_mysql;

use adapt\sql_install\model_sql_script;

defined('ADAPT_STARTED') or die();

/**
 * This bundle contains the code to install MySQL database scripts
 * contained in other bundles
 *
 * @author Joe Hockaday <jdhockad@hotmail.com>
 * @license MIT
 */

class bundle_sql_install_mysql extends \adapt\bundle
{
    /**
     * @var array
     */
    protected $_mysql_dirs;

    const DIALECT = 'mysql';

    /**
     * bundle_sql_install_mysql constructor.
     * @param mixed $data
     */
    public function __construct($data)
    {
        parent::__construct('sql_install_mysql', $data);

        $this->_mysql_dirs = array();
        $this->register_config_handler('sql_install_mysql', 'mysql_dir', 'process_mysql_directory');
    }

    /**
     * Bundle specific boot code to go here
     * @return bool
     */
    public function boot()
    {
        if (parent::boot()) {
            return true;
        }

        return false;
    }

    /**
     * Bundle specific install code to go here
     * @return bool
     */
    public function install()
    {
        if (parent::install()) {

            return true;
        }

        return false;
    }

    /**
     * Reads the MySQL directories from the bundle.xml files and stores them for later
     * @param mixed $bundle
     * @param mixed $tag_data
     */
    public function process_mysql_directory($bundle, $tag_data)
    {
        if ($bundle instanceof \adapt\bundle && $tag_data instanceof \adapt\xml){
            $this->register_install_handler($this->name, $bundle->name, 'process_mysql_files');

            $path = ADAPT_PATH . $bundle->name . '/' . $bundle->name . '-' . $bundle->version . $tag_data->get(0);

            $this->_mysql_dirs[$bundle->name] = $path;
        }
    }

    /**
     * Processes the MySQL directories and loads all the files in them
     * @param mixed $bundle
     */
    public function process_mysql_files($bundle)
    {
        if (count($this->_mysql_dirs) > 0) {
            if ($this->_mysql_dirs[$bundle->name]) {
                // Parse and run all SQL files
                $files = glob($this->_mysql_dirs[$bundle->name] . '/*.sql');
                foreach ($files as $file) {
                    $sql_script = new model_sql_script();
                    if ($sql_script->safe_to_run($bundle->name, $file, self::DIALECT)) {
                        $cmd = 'mysql --host=' . $this->data_source->get_host(true)['host'] . ' -u ' . $this->data_source->get_host(true)['username'] . ' -p' . $this->data_source->get_host(true)['password'] . ' -D ' . $this->data_source->get_host(true)['schema'] . ' --default-character-set=' . $this->setting('mysql.default_character_set') . ' < ' . $file;
                        exec($cmd);
                        $sql_script->ran_script($bundle->name, $bundle->version, $file, self::DIALECT);
                    }
                }
            }
        }
    }
}