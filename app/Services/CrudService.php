<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CrudService
{
    protected ?Model $model = null;

    public function setModel(Model $model): self
    {
        $this->model = $model;
        return $this;
    }

    public function getModel(): Model
    {
        if (!$this->model) {
            throw new \Exception("Model belum di-set. Jalankan setModel() terlebih dahulu.");
        }
        return $this->model;
    }

    public function all()
    {
        return $this->getModel()->all();
    }

    public function find($id)
    {
        return $this->getModel()->findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->getModel()->create($data);
    }

    public function update($id, array $data)
    {
        $record = $this->find($id);
        $record->update($data);
        return $record;
    }

    public function delete($id)
    {
        $record = $this->find($id);
        return $record->delete();
    }

    public function toArray($id = null): array
    {
        if ($id) {
            return $this->find($id)->toArray();
        }

        return $this->all()->toArray();
    }
}