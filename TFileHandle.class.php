<?php
class TFileHandle {

	function __construct(){
	}

	/**
     * To get substring from right
     * @author zhangjin
     * @createTime 2011-12-16 14:41:19
     * @param $str string the String to be substred
     * @param $length int the number count from right to cut
     * @return string the substring
     */
	static public function substrRight($str, $length)
	{
		$strLen = strlen($str);
		if($strLen <= $length)
		{
			return $str;
		}
		else
		{
			return substr($str, $strLen-$length, $length);
		}
	}

	static public function getFNameNoExt($pFileName){
		$fName = self::getFileName($pFileName);
		$ext = self::getFileExt($fName);
		if(empty($ext)){
			return $fName;
		}
		$strLen = strlen($fName);
		$extLen = strlen($ext);
		return substr($fName,0,$strLen-$extLen-1);
	}

	static public function getFileExt($pFileName){
        $pos = strpos($pFileName,'?');
        if($pos){
        	$pFileName=substr($pFileName,0,$pos);
        }
        $name = self::getFileName($pFileName);
        $ext = explode(".", $name);
        return (count($ext)==1) ? "" : array_pop($ext);
    }

	static public function getFileName($pFileName){
		$arr = explode('/', $pFileName);
		$name = array_pop($arr);
		return $name;
	}

	/**
     * 在给定的路径下面根据时间生成唯一的文件名称
     * @author
     * @createTime
     * @param $pPath file name + path
     * @param $pFileType file type
     * @return fileName
     */
    function getFileNameByTime($pPath, $pFileType)
    {
        //如果没有在后面加上路径分隔符，则加上
        $pPath = CClass_Tools_File::getValidPath($pPath);

        do
        {
            $fileName = CClass_Tools_String::getDateTime(14);
            list($usec, $sec) = explode(" ",microtime());
            $fileName .= CClass_Tools_String::right((int)($usec*1000000)+10000000, 6);
        }while(file_exists($pPath.$fileName.".".$pFileType));

        return $fileName.".".$pFileType;
    }
}