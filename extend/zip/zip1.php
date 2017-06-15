<?php
/**
 * 压缩文件
 * @param string $path 需要压缩的文件[夹]路径
 * @param string $savedir 压缩文件所保存的目录
 * @return array zip文件路径
 */

      function folderToZip($folder, &$zipFile, $exclusiveLength) { 
        $handle = opendir($folder); 
        while (false !== $f = readdir($handle)) { 
          if ($f != '.' && $f != '..') { 
            $filePath = "$folder/$f"; 
            // Remove prefix from file path before add to zip. 
            $localPath = substr($filePath, $exclusiveLength); 
            if (is_file($filePath)) { 
              $zipFile->addFile($filePath, $localPath); 
            } elseif (is_dir($filePath)) { 
              // Add sub-directory. 
              $zipFile->addEmptyDir($localPath); 
              folderToZip($filePath, $zipFile, $exclusiveLength); 
            } 
          } 
        } 
        closedir($handle); 
      } 
      
      function zipDir($sourcePath, $outZipPath)
      {
          $pathInfo = pathInfo($sourcePath);
          $parentPath = $pathInfo['dirname'];
          $dirName = $pathInfo['basename'];
      
          $z = new ZipArchive();
          $z->open($outZipPath, ZIPARCHIVE::CREATE);
          $z->addEmptyDir($dirName);
          folderToZip($sourcePath, $z, strlen("$parentPath/"));
          $z->close();
      }

      function delDir($dir)
      {
        //先删除目录下的文件：
          $dh=opendir($dir);
          while ($file=readdir($dh)) {
            if($file!="." && $file!="..") {
              $fullpath=$dir."/".$file;
              if(!is_dir($fullpath)) {
                  unlink($fullpath);
              } else {
                  deldir($fullpath);
              }
            }
          }
         
          closedir($dh);
          //删除当前文件夹：
          if(rmdir($dir)) {
            return true;
          } else {
            return false;
          }
     }
?> 
