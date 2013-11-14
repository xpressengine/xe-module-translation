<?php
class Upload
{
    var $mSavePath;
    var $mValidFile = array();
    var $mUnvalidFile = array();
    var $mMaxSize = 0;//file max size

    /**
     * create upload file obj, initial path to save, default is null
     * @author
     * @createTime
     * @param $pSavePath the path to save the
     */
    function __construct($pSavePath = "")
    {
        $this->setSavePath($pSavePath);
    }

    /**
     * save the upload file, success return true, otherwise return false
     * @author
     * @createTime
     * @param $pUploadFile upload file name(the name of input in the form)
     * @param $pSaveFileName the name to save
     * @return success return 1，file is too big return -3;file type is not right reutn -2;faile to upload return -1
     */
    function saveFile($pUploadFile, $pSaveFileName="uploadFile")
    {
        if (is_uploaded_file($_FILES[$pUploadFile]['tmp_name']))
        {
            $ext = TFileHandle::getFileExt($_FILES[$pUploadFile]['name']);

            if(""==$pSaveFileName)
            {
            	return -2;
            }
            else
            {
                $pSaveFileName .= ".".$ext;
            }
            if($this->checkValidateFormat($ext))
            {
                if($this->checkFileSize($_FILES[$pUploadFile]['size']))
                {
                    $path = $this->getSavePath();
                    if(move_uploaded_file($_FILES[$pUploadFile]['tmp_name'], $path.$pSaveFileName))
                        return $pSaveFileName;
                }
                else
                {
                    return -3;
                }
            }
            else
            {
                return -2;
            }
            return -4;
        }
        else
        {
            return -1;
        }
    }

    /**
     * set upload file path to save,path is end with '/', if not end of '/' than it will be added
     * @author
     * @createTime
     * @param $pSavePath upload file to save path
     */
    function setSavePath($pSavePath)
    {
        if(substr($pSavePath, strlen($pSavePath)-1) != "/")
            $this->mSavePath = $pSavePath."/";
        else
            $this->mSavePath = $pSavePath;
    }

    /**
     * get file save path
     * @author
     * @createTime
     * @return return file save path
     */
    function getSavePath()
    {
        return $this->mSavePath;
    }

    /**
     * file ext vlidate
     * @author
     * @createTime
     * @param $pCheckFileType file ext name
     * @return if not allow return false,or return true
     */
    function checkValidateFormat($pCheckFileType)
    {
        //if have prove file type, the not allow files are not working
        if(count($this->mValidFile)>0)
        {
            foreach($this->mValidFile as $valid_file)
            {
                if(strtolower($valid_file)==strtolower($pCheckFileType))
                {
                    //allow upload
                    return true;
                }
            }
            return false;
        }

        if(count($this->mUnvalidFile)>0)
        {
            foreach($this->mUnvalidFile as $unvalid_file)
            {
                if(strtolower($unvalid_file)==strtolower($pCheckFileType))
                {
                    //not allow upload
                    return false;
                }
            }
            return true;
        }
        else
        {
            return true;
        }
    }

    /**
     * check file size
     * @author
     * @createTime
     * @param $pFileSize file size(bytes)
     * @renturn true-allow upload, else not allow
     */
    function checkFileSize($pFileSize)
    {
        if(0==$this->mMaxSize || $this->mMaxSize>$pFileSize)
            return true;
        else
            return false;
    }

    /**
     * 设置上传文件的最大字节限制
     * @author
     * @createTime
     * @param $pMaxSize 文件大小(bytes) 0:表示无限制
     */
    function setMaxSize($pMaxSize)
    {
        $this->mMaxSize = $pMaxSize;
    }

    /**
     * 增加一个可以上传文件的类型
     * @author
     * @createTime
     * @param $pFileType 文件类型
     */
    function addValidType($pFileType)
    {
        $this->mValidFile[] = $pFileType;
    }

    /**
     * 增加一个不可以上传文件的类型
     * @author
     * @createTime
     * @param $pFileType 文件类型
     */
    function addUnvalidType($pFileType)
    {
        $this->mUnvalidFile[] = $pFileType;
    }

    /**
     * 清空对可上传文件的限制
     * @author
     * @createTime 2004-03-31
     */
    function clearValidFile()
    {
        $this->mValidFile = array();
    }

    /**
     * 清空对不可上传文件的限制
     * @author
     * @createTime 2004-03-31
     */
    function clearUnvalidFile()
    {
        $this->mUnvalidFile = array();
    }
}