<?php

namespace App\Services;

use Throwable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use App\Services\Utils\FileUploadService;

/**
 * BaseService
 */
class BaseService
{
    protected $model;
    protected $fileUploadService;

    /**
     * __construct
     *
     * @param  mixed $model
     * @return void
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->fileUploadService = app(FileUploadService::class);
    }

    /**
     * uploadFile
     *
     * @param  mixed $file
     * @param  mixed $old_name
     * @param  mixed $upload_path
     * @return void
     */
    public function uploadFile($file, $old_name = null, $upload_path = null)
    {
        $path = $upload_path ? $upload_path : $this->model::FILE_STORE_PATH;
        if ($old_name) {
            // Delete and upload
            // Delete old
            $this->fileUploadService->delete($path . '/' . $old_name);
            // Upload new
            return $this->fileUploadService->upload($file, ($path ?? null));
        } else {
            // Upload
            return $this->fileUploadService->upload($file, ($path ?? null));
        }
    }

    /**
     * createOrUpdateWithFile
     *
     * @param  mixed $data
     * @param  mixed $file_field_name
     * @param  mixed $id
     * @return void
     */
    public function createOrUpdateWithFile(array $data, $file_field_name, $id = null)
    {
        try {
            if ($id) {
                $data['updated_by'] = Auth::id();
                $object = $this->model->findOrFail($id);
                if (isset($data[$file_field_name]) && $data[$file_field_name] != null) {
                    $data[$file_field_name] = $this->uploadFile($data[$file_field_name], $object->$file_field_name);
                }
                return $object->update($data);
            } else {
                $data['created_by'] = Auth::id();
                if (isset($data[$file_field_name]) && $data[$file_field_name] != null) {
                    $data[$file_field_name] = $this->uploadFile($data[$file_field_name]);
                }
                return $this->model::create($data);
            }
        } catch (Throwable $th) {
            throw $th;
        }
    }

    /**
     * createOrUpdate
     *
     * @param  mixed $data
     * @param  mixed $id
     * @return void
     */
    public function createOrUpdate(array $data, $id = null)
    {
        try {
            if ($id) {
                $data['updated_by'] = Auth::id();
                return $this->model->findOrFail($id)->update($data);
            } else {
                $data['created_by'] = Auth::id();
                return $this->model::create($data);
            }
        } catch (Throwable $th) {
            throw $th;
        }
    }

    /**
     * get
     *
     * @param  mixed $id
     * @param  mixed $with
     * @param  mixed $limit
     * @return void
     */
    public function get($id = null, $with = [], $limit = null)
    {
        try {
            if ($id) {
                $data = $this->model->with($with)->find($id);
                return $data ? $data : false;
            } else {
                return $this->model->with($with)->limit($limit)->get();
            }
        } catch (Throwable $th) {
            throw $th;
        }
    }

    /**
     * getActiveData
     *
     * @param  mixed $id
     * @param  mixed $with
     * @return void
     */
    public function getActiveData($id = null, $with = [], $select = null, $where = null)
    {
        try {
            if ($id) {
                $data = $this->model->with($with)
                    ->when(!is_null($where), function ($query) use ($where) {
                        return $query->where($where);
                    })
                    ->when(!is_null($select), function ($query) use ($select) {
                        return $query->select($select);
                    })
                    ->active()->find($id);
                return $data ? $data : false;
            } else {
                return $this->model->with($with)
                    ->when(!is_null($where), function ($query) use ($where) {
                        return $query->where($where);
                    })
                    ->when(!is_null($select), function ($query) use ($select) {
                        return $query->select($select);
                    })
                    ->active()->get();
            }
        } catch (Throwable $th) {
            throw $th;
        }
    }

    /**
     * delete
     *
     * @param  mixed $id
     * @return void
     */
    public function delete($id)
    {
        try {
            $data = $this->model::findOrFail($id);
            return $data->delete();
        } catch (Throwable $th) {
            throw $th;
        }
    }


    public function __call($name, $arguments)
    {
        $this->model->{$name}($arguments);
    }
}
