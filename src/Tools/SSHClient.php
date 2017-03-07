<?php
namespace Addons\Core\Tools;
/**
 * SSH Library includes the ability to connect to a host and verify its key, login with a username/password or a ssh key.
 * Currently capable of SCP, directory navigation, and directory listing
 * 
 * 
 * @package    Jexmex/SSH
 * @author     Jexmex <sparks.craig@gmail.com>
 * @copyright  (c) 2011 Jexmex
 * @license    MIT
 */
class SSHClient
{
	/**/
	protected $_connected = FALSE;
	
	protected $_conn_link;

	private $_sftp;
	
	protected $_config = array(
		'host' => 'SSH\'s ip',
		'host_fingerprint' => NULL,
		'port' => 22,
		'authentication_method' => 'PASS', //PASS KEY 
		'user' => NULL, //set it if authentication_method == 'PASS'
		'password' => NULL, //set it if authentication_method == 'PASS'
		'pub_key' => NULL, //set it if authentication_method == 'KEY'
		'private_key' => NULL, //set it if authentication_method == 'KEY'
		'passphrase' => NULL, //set it if authentication_method == 'KEY'
		'auto_connect' => TRUE,
	);
	
	/**
	 * The constructor method that initiates the class
	 * 
	 * Pass a configuration array with the following values
	 * 
	 * array (
	 *      'host' => 'THE.HOST.TO.CONNECT.TO', //IP or Hostname
	 *      'host_fingerprint' => 'HOSTFINGERPRINT', //The fingerprint of the host to authenticate with.  If this is NULL no check will be done (but this is not recommened)
	 *      'port' => '22', //The port to use to connect
	 *      'user' => 'myuser', //The user to connect with
	 *      'authentication_method' => 'KEY', //The authentication method, either KEY or PASS
	 *      'password' => NULL, //The password to use or NULL if using key authentication
	 *      'pub_key' => '/location/to/pub/ssh/key', //The location of the servers/users public ssh key. NULL if using password
	 *      'private_key' => '/location/to/private/ssh/key', //The location of the servers/users private ssh key. NULL if using password
	 *      'passphrase' => 'thisismypassphrase', //The passphrase for the ssh key, if there is not one, set to NULL
	 *      'auto_connect' => TRUE, //Should the server be auto-connected to during class initialization (defaults to TRUE)
	 * )
	 * 
	 * @param $config array Array of configuration items
	 */
	public function __construct(array $config)
	{
		$this->_config = $config + $this->_config;
		
		//This ensures the fingerprint is formatted the same as the way ssh2_fingerprint returns it
		!is_null($this->_config['host_fingerprint']) && $this->_config['host_fingerprint'] = strtoupper(str_replace(':', '', $this->_config['host_fingerprint']));
		
		if($this->_config['auto_connect'] == TRUE)
		{
			$this->connect();
		}
	}
	
	/**
	 * Connect to host
	 * 
	 * Connects to the host.  Throws exception if the host is unable to be connected to.  Will automatically
	 * verify the host fingerprint, if one was provided, and throw an exception if the fingerprint is not
	 * verified.
	 * 
	 */
	public function connect()
	{
		//Attempt to connect to host
		$link = ssh2_connect($this->_config['host'], $this->_config['port']);
		
		//If host connection fails, throw exception
		if(!$link)
		{
			throw new \Exception('Unable to connect to '.$host.' on port '.$port);
		}
		else
		{
			//Assign the connection link to the class property
			$this->_conn_link = $link;
			
			//If host fingerprint is not NULL, attempt to verify fingerprint
			if(!is_null($this->_config['host_fingerprint'])) 
			{
				$verify = $this->verify_host_fingerprint();
				
				//If the fingerprint is not verified, throw exception
				if(!$verify)
				{
					throw new \Exception('Unable to verify host fingerprint');
				}
			}
		}
		
		//Attempt to login user
		if($this->_config['authentication_method'] == 'KEY')
		{
			$this->_connected = $this->login_key();
		}
		else
		{
			$this->_connected = $this->login_password();
		}

		$this->_connected && $this->_sftp = ssh2_sftp($link);
	}
	
	/**
	 * Connection check
	 * 
	 * This method is suppose to check to see if the host is still connected to, but at this time,
	 * I am unaware of a way to do this with SSH2 functions.
	 * 
	 * @ignore This is currently unused and should be ignored
	 */
	public function check_connection()
	{
		/* AS OF THIS TIME, I DO NOT THINK ITS POSSIBLE TO CHECK THE CONNECTION*/
		return $this->_connected;
	}
	
	/**
	 * Verify host fingerprint
	 * 
	 * Verifies the host fingerprint.
	 * 
	 * @return TRUE on success, FALSE on failure
	 */
	protected function verify_host_fingerprint()
	{
		//Get the hosts fingerprint
		$fingerprint = ssh2_fingerprint($this->_conn_link);

		//Check the returned fingerprint, to the one expected
		if($this->_config['host_fingerprint'] === $fingerprint)
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	/**
	 * Login using a key
	 * 
	 * This will attempt to login using the provided user and a hash key.
	 * 
	 * @return bool TRUE on success, FALSE on failure 
	 */
	public function login_key()
	{
		//TODO: add location for pub/private key files
		return ssh2_auth_pubkey_file($this->_conn_link, $this->_config['pub_key'], $this->_config['private_key'], $this->_config['passphrase']);
	}
	
	/**
	 * Login using password
	 * 
	 * Attempts to login using the provided user and a password
	 * 
	 * @return bool TRUE on success, FALSE on failure
	 */
	public function login_password()
	{
		return ssh2_auth_password($this->_conn_link, $this->_config['user'], $this->_config['password']);
	}

	/**
	 * Exec a command
	 *
	 * 
	 * @param $command string 命令
	 * @param 
	 */
	public function exec($command, $pty = NULL, $env = array(), $width = 80, $height = 25, $width_height_type = SSH2_TERM_UNIT_CHARS)
	{
		return ssh2_exec($this->_conn_link, $command);
	}
	
	/**
	 * Sends a file to the remote server using scp
	 * 
	 * Attempts to send a file via SCP to the remote server currently connected to
	 * 
	 * @param $local_filepath string The path to the file to send
	 * @param $remote_filepath string The path to the remote location to save the file
	 */
	public function send_file($local_filepath, $remote_filepath, $create_mode = 0644)
	{
		
		$local_filepath = $this->format_path($local_filepath);
		$remote_filepath = $this->format_path($remote_filepath);

		//Attempt to send the file
		return ssh2_scp_send($this->_conn_link, $local_filepath, $remote_filepath, $create_mode);
	}

	/**
	 * Requests a file from the remote server using SCP
	 * 
	 * Attempts to request and save a file from the currently connected to server using SCP.
	 * 
	 * @param $local_filepath string The path to save the file to on local server
	 * @param $remote_filepath string The path to the remote file that is being requested
	 */
	 
	public function receive_file($remote_filepath, $local_filepath)
	{
		$local_filepath = $this->format_path($local_filepath);
		$remote_filepath = $this->format_path($remote_filepath);

		return ssh2_scp_recv($this->_conn_link, $remote_filepath, $local_filepath);
	}

	public function rename($from, $to)
	{
		return mv($from, $to);
	}

	public function chmod($filename, $mode)
	{
		return ssh2_sftp_chmod($this->_sftp, $filename, $mode);
	}

	public function lstat($path)
	{
		return ssh2_sftp_lstat($this->_sftp, $path);
	}

	public function realpath($filename)
	{
		return ssh2_sftp_realpath($this->_sftp, $filename);
	}

	public function stat($path)
	{
		return ssh2_sftp_stat($this->_sftp, $path);
	}

	public function unlink($filename)
	{
		return ssh2_sftp_unlink($this->_sftp, $filename);
	}

	public function delete($filename)
	{
		return $this->unlink($filename);
	}

	public function readlink($link)
	{
		return ssh2_sftp_readlink($this->_sftp, $link);
	}

	public function symlink($target, $link)
	{
		return ssh2_sftp_symlink($this->_sftp, $target, $link);
	}

	public function link($target, $link)
	{
		if (!ini_get('allow_url_fopen'))
		{
			return $this->exec(sprintf('ln -d -f "%s" "%s"', $target, $link)) !== FALSE;
		}
		else
		{
			$target = $this->format_sftp_path($target);
			$link = $this->format_sftp_path($link);
			return link($target, $link);
		}
		
	}

	public function mv($from, $to)
	{
		return ssh2_sftp_rename($this->_sftp, $from, $to);
	}

	public function mkdir($pathname, $mode = 0777, $recursive = FALSE)
	{
		return ssh2_sftp_mkdir($this->_sftp, $pathname, $mode, $recursive);
	}

	public function rmdir($dirname)
	{
		return ssh2_sftp_rmdir($this->_sftp, $dirname);
	}

	public function copy($source, $dest)
	{
		if (!ini_get('allow_url_fopen'))
		{
			return $this->exec(sprintf('cp "%s" "%s"', $source, $dest)) !== FALSE;
		}
		else
		{
			$source = $this->format_sftp_path($source);
			$dest = $this->format_sftp_path($dest);
			return copy($source, $dest);
		}
	}

	public function __call($name, $arguments)
	{
		//无需转换的函数
		$list = array('pathinfo','basename','mb_basename','dirname','fwrite','fclose','feof','fflush','fgetc','fgetcsv','fgets','fgetss','flock','fpassthru','fputcsv','fputs','fwirte','fread','fscanf','fseek','fstat','ftell','ftruncate','rewind',);
		if (in_array($name, $list)) return call_user_func_array($name, $arguments);
		//没有开启协议
		if (!ini_get('allow_url_fopen'))
		{
			$list = array(
				'filesize' => 'size','fileowner' => 'uid','filegroup' => 'gid','fileperms' => 'mode','fileatime' => 'atime','filemtime' => 'mtime',
				'file_exists' => 'file_exists','is_dir' => 'is_dir','is_file' => 'is_file','is_link' => 'is_link','is_executable' => 'is_executable','is_readable' => 'is_readable','is_writable' => 'is_writable','is_writeable' => 'is_writable','filetype' => 'filetype'
			);
			
			if (!array_key_exists($name, $list)) 
				throw new \Exception('"allow_url_fopen" is off!');

			$data = @ssh2_sftp_stat($arguments[0]);
			$data['file_exists'] = $data !== FALSE;
			$data['is_dir'] = ($data['mode'] & 0x4000) == 0x4000;
			$data['is_file'] = ($data['mode'] & 0x8000) == 0x8000;
			$data['is_link'] = ($data['mode'] & 0xA000) == 0xA000;
			$data['is_executable'] = ($data['mode'] & 0x0040) == 0x0040;
			$data['is_readable'] = ($data['mode'] & 0x0100) == 0x0100;
			$data['is_writable'] = ($data['mode'] & 0x0080) == 0x0080;
			if (( $perms  &  0xC000 ) ==  0xC000 ) // Socket
				$data['filetype']  =  'socket' ;
			elseif (( $perms  &  0xA000 ) ==  0xA000 ) // Symbolic Link
				$data['filetype']  =  'link' ;
			elseif (( $perms  &  0x8000 ) ==  0x8000 ) // Regular
				$data['filetype']  =  'file' ;
			elseif (( $perms  &  0x6000 ) ==  0x6000 ) // Block special
				$data['filetype']  =  'block' ;
			elseif (( $perms  &  0x4000 ) ==  0x4000 ) // Directory
				$data['filetype']  =  'dir' ;
			elseif (( $perms  &  0x2000 ) ==  0x2000 ) // Character special
				$data['filetype']  =  'char' ;
			elseif (( $perms  &  0x1000 ) ==  0x1000 ) // FIFO pipe
				$data['filetype']  =  'fifo' ;
			else // Unknown
				$data['filetype']  =  'unknown' ;

			return $data[($list[$name])];
		}
		
		//需要转换路径的的函数
		$list = array(
			'file_exists','is_dir','is_executable','is_file','is_link','is_readable','is_writable','is_writeable',
			'lchgrp','lchown','linkinfo',
			'tempnam',
			'file_get_contents','file_put_contents','file','readfile','parse_ini_file',
			'fopen',
			'disk_free_space','diskfreespace','disk_total_space',
			'chown','chgrp','fileatime','filectime','filegroup','fileinode','filemtime','fileowner','fileperms','filesize','filetype','touch',
		);

		if (!in_array($name, $list))
			throw new \Exception('Call to undefined function: '.__CLASS__.'::'.$name.'()');
		//将路径变为SFTP路径
		$arguments[0] = $this->format_sftp_path($arguments[0]);
		return call_user_func_array($name, $arguments);
	}

	
	/**
	 * Disconnects from the connected server
	 */
	public function disconnect()
	{    	
		$this->exec('echo "EXITING" && exit;');
		
		$this->_conn_link = NULL;
		$this->_connected = FALSE;
		$this->_sftp = NULL;
	}
	
	/**
	 * Deconstructor called when class instance is destroyed
	 */
	public function __destruct()
	{
		$this->disconnect();
	}

	private function format_path($path)
	{
		preg_match('/\\s/', $path) > 0 && $path = '"'.$path.'"';
		return $path;
	}

	private function format_sftp_path($path)
	{
		return 'ssh2.sftp://'.$this->_sftp.'/'.$path;
	}
}