<?php
declare(strict_types=1);

namespace cccms\storages;

use think\facade\Filesystem;
use cccms\Storage;
use cccms\services\UserService;

class LocalStorage extends Storage
{
    /**
     * 上传文件
     * @param $files
     * @param int|string $folderOrCateId 字符串则为文件夹名称，数字则为文件管理入库上传
     * @return array
     */
    public function upload($files, int|string $folderOrCateId = 0): array
    {
        $res = $this->validateFile($files);
        $this->app->config->set([
            'disks' => [
                'local' => ['root' => $this->getLocalPath()]
            ]
        ], 'filesystem');
        $saveName = [];
        if (is_string($folderOrCateId)) {
            // 字符串模式：编辑器上传，不入库
            foreach ($res as $val) {
                $file_path = str_replace('\\', '/', Filesystem::putFile($folderOrCateId, $val, 'date("Y-m-d")'));
                $saveName[] = [
                    'file_path' => $file_path,
                    'file_url' => $this->request->domain() . '/uploads/' . $file_path,
                    'file_name' => $val->getoriginalName(),
                    'file_size' => $val->getSize(),
                    'file_ext' => $val->getOriginalExtension(),
                    'file_mime' => $val->getOriginalMime(),
                    'file_md5' => $val->md5(),
                    'file_sha1' => $val->sha1(),
                ];
            }
        } else {
            // 数字模式：文件管理页面上传，入库
            $user_id = UserService::instance()->getUserInfo('id');
            foreach ($res as $val) {
                $file_path = str_replace('\\', '/', Filesystem::putFile('files', $val, 'date("Y-m-d")'));
                $saveName[] = [
                    'user_id' => $user_id,
                    'file_path' => $file_path,
                    'file_url' => $this->request->domain() . '/uploads/' . $file_path,
                    'file_name' => $val->getoriginalName(),
                    'file_size' => $val->getSize(),
                    'file_ext' => $val->getOriginalExtension(),
                    'file_mime' => $val->getOriginalMime(),
                    'file_md5' => $val->md5(),
                    'file_sha1' => $val->sha1(),
                    'file_code' => md5(mt_rand($user_id, time()) . $val->hashName() . $val->getPathname()),
                ];
            }
            $this->model->strict(false)->insertAll($saveName);
        }
        return count($saveName) > 1 ? $saveName : $saveName[0];
    }

    /**
     * 删除文件
     * @param int|string $folderOrCateId 数字为文件ID，字符串为文件路径
     * @return bool
     */
    public function delete($folderOrCateId = 0): bool
    {
        if (is_string($folderOrCateId)) {
            // 使用basename + 规范化路径，防止跨目录删除
            $safeName = basename(str_replace('\\', '/', $folderOrCateId));
            $filePath = $this->getLocalPath() . $safeName;
        } else {
            if (empty($folderOrCateId)) return false;
            $fileInfo = $this->model->findOrEmpty($folderOrCateId);
            if (!$fileInfo->isEmpty()) {
                $fileInfo->delete();
            }
            // 磁盘文件路径
            $filePath = $this->getLocalPath() . $fileInfo['file_path'];
        }
        // 路径安全校验：确保文件在允许的目录范围内
        $realPath = realpath($filePath);
        $realBasePath = realpath($this->getLocalPath());
        if ($realPath === false || $realBasePath === false || !str_starts_with($realPath, $realBasePath)) {
            return false;
        }
        // 判断附件是否在磁盘中
        if (file_exists($realPath) && !unlink($realPath)) {
            return false;
        }
        return true;
    }

    public function read()
    {
        // TODO: Implement list() method.
    }
}
