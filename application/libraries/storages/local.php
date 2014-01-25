<?php

class Storages_Local extends Library
{
	protected $relativePath = '';
	protected $relativeURL = '';
	protected $errorMessage = false;

	public function __construct($config = array())
	{
		parent::__construct();

		foreach ( $config as $item => $value )
		{
			$this->$item = $value;
		}
	}

	public function getManifest()
	{
		$params = array(
			'name' => 'Local storage',
			'description' => 'File storage on your local server.',
			'settings' => array(
				array(
					'name' => 'Relative path',
					'keyword' => 'relativePath',
					'type' => 'text',
					'value' => '',
				),
				array(
					'name' => 'Relative URL',
					'keyword' => 'relativeURL',
					'type' => 'text',
					'value' => '',
				),
			),
		);

		return $params;
	}

	public function validateSettings($settings)
	{
		$settings['relativePath'] = trim($settings['relativePath'], '/\\');
		$settings['relativeURL'] = trim($settings['relativeURL'], '/');

		return $settings;
	}

	public function copy($source)
	{
		// Upload path
		$suffix = implode('/', str_split(text_helper::random(4, 'numeric')));
		$path = $this->relativePath . '/' . $suffix;

		$pathinfo = pathinfo($source);
		$extension = isset($pathinfo['extension']) ? $pathinfo['extension'] : '';

		// File name
		$filename = text_helper::random(20);

		// Does upload path exist?
		if ( !$this->createPath($this->relativePath, $suffix) )
		{
			return false;
		}

		loader::helper('file');
		if ( !file_helper::copy($source, BASEPATH . $path . '/' . $filename . ( $extension ? '.' . $extension : '')) )
		{
			return false;
		}

		$file = array(
		    'name' => $filename . ( $extension ? '.' . $extension : ''),
		    'extension' => $extension,
		    'name_raw' => $filename,
		    'type' => '',
		    'size' => round(@filesize(BASEPATH . $path . '/' . $filename . ( $extension ? '.' . $extension : '')) / 1024, 2),
		    'path' => BASEPATH . $path . '/',
		    'path_suffix' => $suffix,
		    'name_temp' => '',
		    'name_original' => $pathinfo['filename'],
		    'image' => 1,
		    'width' => 0,
		    'height' => 0,
		);

		loader::helper('image');

		$file['image'] = image_helper::isImage(BASEPATH . $path . '/' . $filename . ( $extension ? '.' . $extension : ''));
		if ( $file['image'] )
		{
			$dimensions = image_helper::getDimensions(BASEPATH . $path . '/' . $filename . ( $extension ? '.' . $extension : ''));
			$file['width'] = $dimensions[0];
			$file['height'] = $dimensions[1];
		}

		return $file;
	}

	public function download($url, $maxsize)
	{
		// Upload path
		$suffix = implode('/', str_split(text_helper::random(4, 'numeric')));
		$path = $this->relativePath . '/'. $suffix;

		$pathinfo = pathinfo($url);
		$extension = isset($pathinfo['extension']) ? $pathinfo['extension'] : '';

		// Does upload path exist?
		if ( !$this->createPath($this->relativePath, $suffix) )
		{
			return false;
		}

		// File name
		$filename = text_helper::random(20);

		loader::helper('file');
		if ( !file_helper::download($url, BASEPATH . $path, $filename . ( $extension ? '.' . $extension : '')) )
		{
			return false;
		}

		$file = array(
		    'name' => $filename . ( $extension ? '.' . $extension : ''),
		    'extension' => $extension,
		    'name_raw' => $filename,
		    'type' => '',
		    'size' => round(@filesize(BASEPATH . $path . '/' . $filename . ( $extension ? '.' . $extension : '')) / 1024, 2),
		    'path' => BASEPATH . $path . '/',
		    'path_suffix' => $suffix,
		    'name_temp' => '',
		    'name_original' => $pathinfo['filename'],
		    'image' => true,
		    'width' => 0,
		    'height' => 0,
		);

		loader::helper('image');

		$file['image'] = image_helper::isImage(BASEPATH . $path . '/' . $filename . ( $extension ? '.' . $extension : ''));
		if ( $file['image'] )
		{
			$dimensions = image_helper::getDimensions(BASEPATH . $path . '/' . $filename . ( $extension ? '.' . $extension : ''));
			$file['width'] = $dimensions[0];
			$file['height'] = $dimensions[1];
		}

		return $file;
	}

	public function upload($filename, $extensions, $maxsize, $maxdimensions = '')
	{
		if ( $maxdimensions && !is_array($maxdimensions) )
		{
			$maxdimensions = explode('x', $maxdimensions);
		}

		// Upload path
		$suffix = implode('/', str_split(text_helper::random(4, 'numeric')));
		$path = $this->relativePath . '/' . $suffix;

		// Does upload path exist?
		if ( !$this->createPath($this->relativePath, $suffix) )
		{
			return false;
		}

		// Uploader configuration
		$config = array(
			'upload_path' => BASEPATH . $path,
			'allowed_types' => str_replace(',', '|', $extensions),
			'max_size' => $maxsize * 1024,
			'max_width' => isset($maxdimensions[0]) ? $maxdimensions[0] : 0,
			'max_height' => isset($maxdimensions[1]) ? $maxdimensions[1] : 0,
			'encrypt_name' => true,
			'clean_name' => false,
			'overwrite' => true,
		);

		// Load uploader library
		loader::library('uploader', $config);

		if ( !$this->uploader->run($filename) )
		{
			$this->setError($this->uploader->getError());
			return false;
		}

		$file = $this->uploader->getData();
		$file['path_suffix'] = $suffix;

		return $file;
	}

	public function resize($file, $dimensions, $suffix = '', $method = 'preserve')
	{
		// Set dimensions
		if ( !is_array($dimensions) && $dimensions != '' )
		{
			$dimensions = explode('x', $dimensions);
		}

		if ( $dimensions )
		{
			// Load image library
			loader::library('image');

			// Set source
			if ( !$this->image->setSource(BASEPATH . $this->relativePath . '/' . $file['path'] . '/' . $file['name'] . ( $file['suffix'] != '' ? '_' . $file['suffix'] : '' ) . '.' . $file['extension']) )
			{
				$this->setError($this->image->getError());
				return false;
			}

			// Set target
			if ( !$this->image->setTarget(BASEPATH . $this->relativePath . '/' . $file['path'] . '/' . $file['name'] . ( $suffix != '' ? '_' . $suffix : '' ) . '.' . $file['extension']) )
			{
				$this->setError($this->image->getError());
				return false;
			}

			// Resize image
			if ( !$this->image->resize($dimensions[0], $dimensions[1], $method) )
			{
				$this->setError($this->image->getError());
				return false;
			}

			$thumb = $this->image->getData();
		}
		else
		{
			$source = BASEPATH . $this->relativePath . '/' . $file['path'] . '/' . $file['name'] . ( $file['suffix'] != '' ? '_' . $file['suffix'] : '' ) . '.' . $file['extension'];
			$target = BASEPATH . $this->relativePath . '/' . $file['path'] . '/' . $file['name'] . ( $suffix != '' ? '_' . $suffix : '' ) . '.' . $file['extension'];

			loader::helper('file');

			$thumb = array(
				'path' => $file['path'],
				'name' => $file['name'],
				'suffix' => $suffix,
				'extension' => $file['extension'],
				'size' => $file['size'],
				'width' => $file['width'],
				'height' => $file['height'],
			);

			return file_helper::copy($source, $target) ? $thumb : false;
		}

		return $thumb;
	}

	public function thumbnail($file, $x1, $y1, $x2, $y2, $dimensions, $suffix = '')
	{
		// Set dimensions
		if ( !is_array($dimensions) && $dimensions != '' )
		{
			$dimensions = explode('x', $dimensions);
		}

		// Load image library
		loader::library('image');

		// Set suffix
		if ( $suffix != '' )
		{
			$this->image->setSuffix('_' . $suffix);
		}

		// Set source
		if ( !$this->image->setSource(BASEPATH . $this->relativePath . '/' . $file['path'] . '/' . $file['name'] . ( $file['suffix'] != '' ? '_' . $file['suffix'] : '' ) . '.' . $file['extension']) )
		{
			$this->setError($this->image->getError());
			return false;
		}

		// Resize image
		if ( !$this->image->thumbnail($dimensions[0], $dimensions[1], $x1, $y1, $x2, $y2) )
		{
			$this->setError($this->image->getError());
			return false;
		}

		$thumb = $this->image->getData();

		return $thumb;
	}

	public function rotate($file, $angle = 90)
	{
		// Load image library
		loader::library('image');

		// Set source
		if ( !$this->image->setSource(BASEPATH . $this->relativePath . '/'. $file['path'] . '/' . $file['name'] . ( $file['suffix'] != '' ? '_' . $file['suffix'] : '' ) . '.' . $file['extension']) )
		{
			$this->setError($this->image->getError());
			return false;
		}

		// Resize image
		if ( !$this->image->rotate($angle) )
		{
			$this->setError($this->image->getError());
			return false;
		}

		$thumb = $this->image->getData();

		return $thumb;
	}

	public function delete($path, $name, $ext, $suffix = '')
	{
		$path = BASEPATH . $this->relativePath . ( $path ? '/' . $path : '' ) . '/'. $name . ( $suffix != '' ? '_' . $suffix : '' ) . '.' . $ext;

		return @file_exists($path) ? @unlink($path) : true;
	}

	public function getFileURL($path, $name, $ext, $suffix = '', $stamp = '')
	{
		$path = config::baseURL($this->relativeURL . ( $path ? '/' . $path : '' ) . '/' . $name . ( $suffix != '' ? '_' . $suffix : '' ) . '.' . $ext . ( $stamp ? '?s=' . $stamp : '' ));

		return $path;
	}

	public function getFileHost()
	{
		return config::baseURL($this->relativePath);
	}

	public function getFilePath($path, $name, $ext, $suffix = '')
	{
		return BASEPATH . $this->relativePath . ( $path ? '/' . $path : '' ) . '/' . $name . ( $suffix != '' ? '_' . $suffix : '' ) . '.' . $ext;
	}

	protected function createPath($relative, $suffix)
	{
		// Does upload path exist?
		if ( !@is_dir(BASEPATH . $relative . '/' . $suffix) )
		{
			if ( !@mkdir(BASEPATH . $relative . '/' . $suffix, octdec(config::item('folder_chmod')), true) )
			{
				$this->setError(__('path_not_created', 'uploader'));
				return false;
			}

			$path = '';
			foreach ( explode('/', $suffix) as $folder )
			{
				$path = $path . '/' . $folder;
				@chmod(BASEPATH . $relative . $path, octdec(config::item('folder_chmod')));
			}
		}

		return true;
	}

	/**
	* Set error message.
	*
	* @param  string  error message
	*/
	protected function setError($error)
	{
		$this->errorMessage = $error;
	}

	/**
	* Get error message.
	*
	* @return  string
	*/
	public function getError()
	{
		if ( $this->errorMessage == '' )
		{
			return '';
		}

		return $this->errorMessage;
	}
}