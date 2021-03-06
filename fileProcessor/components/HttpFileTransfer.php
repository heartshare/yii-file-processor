<?php
/**
 *
 */

namespace fileProcessor\components;

use CComponent;
use ErrorException;
use fileProcessor\helpers\FPM;

/**
 * Author: Ivan Pushkin
 * Email: metal@vintage.com.ua
 */
abstract class HttpFileTransfer extends CComponent implements IFileTransfer
{
	/**
	 * @var null|string
	 */
	private $_baseDestinationDir;

	/**
	 * @var int|null
	 */
	private $_maxFilesPerDir;

	/**
	 * Constructor.
	 *
	 * @param string $baseDestinationDir base destination dir for transfer.
	 * @param integer $maxFilesPerDir maximum files count per dir.
	 */
	public function __construct($baseDestinationDir = null, $maxFilesPerDir = null)
	{
		$this->_baseDestinationDir = $baseDestinationDir;
		$this->_maxFilesPerDir = $maxFilesPerDir;
	}

	/**
	 * Get base destination dir.
	 *
	 * @return string
	 */
	public function getBaseDestinationDir()
	{
		return $this->_baseDestinationDir;
	}

	/**
	 * Get maximum files count per dir param.
	 *
	 * @return integer
	 */
	public function getMaxFilesPerDir()
	{
		return $this->_maxFilesPerDir;
	}

	/**
	 * Save uploaded file and return full file name.
	 *
	 * @param \CUploadedFile $uploadedFile uploaded file.
	 *
	 * @throws \CException
	 * @return integer file id
	 */
	public function saveUploadedFile(\CUploadedFile $uploadedFile)
	{
		$id = $this->saveMetaDataForUploadedFile($uploadedFile);

		$dirName = $this->getBaseDestinationDir() . DIRECTORY_SEPARATOR . floor($id / $this->getMaxFilesPerDir());

		if (!is_dir($dirName)) {
			FPM::mkdir($dirName, 0777, true);
		}
		$realName = pathinfo($uploadedFile->getName(), PATHINFO_FILENAME);
		$ext = \mb_strtolower(pathinfo($uploadedFile->getName(), PATHINFO_EXTENSION), 'UTF-8');
		$ext = $ext ? '.' . $ext : null;
		$fileName = $dirName . DIRECTORY_SEPARATOR . $id . '-' . $realName . $ext;

		$uploadedFile->saveAs($fileName);

		return $id;
	}

	/**
	 * @param $file
	 *
	 * @return mixed
	 * @throws \CException
	 */
	public function saveFile($file)
	{
		$id = $this->saveMetaDataForFile($file);
		$realName = pathinfo($file, PATHINFO_FILENAME);
		$ext = \mb_strtolower(pathinfo($file, PATHINFO_EXTENSION), 'UTF-8');

		$dirName = $this->getBaseDestinationDir() . DIRECTORY_SEPARATOR . floor($id / $this->getMaxFilesPerDir());

		if (!is_dir($dirName)) {
			FPM::mkdir($dirName, 0777, true);
		}

		$fileName = $dirName . DIRECTORY_SEPARATOR . $id . '-' . $realName . ($ext ? '.' . $ext : null);

		$this->putImage($ext, $file, $fileName);

		return $id;
	}

	/**
	 * @param string $file path to the file
	 *
	 * @throws \Exception
	 * @return mixed
	 */
	public function saveFileByCopy($file)
	{
		$id = $this->saveMetaDataForFile($file);
		$realName = pathinfo($file, PATHINFO_FILENAME);
		$ext = \mb_strtolower(pathinfo($file, PATHINFO_EXTENSION), 'UTF-8');

		$dirName = $this->getBaseDestinationDir() . DIRECTORY_SEPARATOR . floor($id / $this->getMaxFilesPerDir());

		if (!is_dir($dirName)) {
			FPM::mkdir($dirName, 0777, true);
		}

		$fileName = $dirName . DIRECTORY_SEPARATOR . $id . '-' . $realName . ($ext ? '.' . $ext : null);

		if (!copy($file, $fileName)) {
			throw new \Exception('Can not copy file from ' . $file . ' to ' . $fileName);
		}

		return $id;
	}

	/**
	 * @param $ext
	 * @param $img
	 * @param null $file
	 *
	 * @throws \Exception
	 */
	public function putImage($ext, $img, $file = null)
	{
		switch ($ext) {
			case "png":
				imagepng($img, $file, 100);
				break;
			case "jpeg":
				imagejpeg($img, $file, 100);
				break;
			case "jpg":
				imagejpeg($img, $file, 100);
				break;
			case "gif":
				imagegif($img, $file);
				break;
			default:
				throw new \Exception('Unknown extension: ' . $ext);
				break;
		}
	}

	/**
	 * Delete file
	 *
	 * @param integer $id file id
	 *
	 * @return boolean
	 */
	public function deleteFile($id)
	{
		if (!(int)$id) {
			return false;
		}

		$dirName = $this->getBaseDestinationDir() . DIRECTORY_SEPARATOR . floor($id / $this->getMaxFilesPerDir());

		$meta = FPM::transfer()->getMetaData($id);
		$fileName = $dirName . DIRECTORY_SEPARATOR . $id . '-' . $meta['real_name'] . '.' . $meta['extension'];

		if (is_file($fileName)) {
			$result = unlink($fileName) && $this->deleteMetaData($id) ? true : false;
		} else {
			$result = false;
		}

		return $result;
	}
}
