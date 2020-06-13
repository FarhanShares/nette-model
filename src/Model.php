<?php

declare(strict_types=1);

namespace Farhanianz\NetteModel;

use Farhanianz\NetteModel\ModelConfig;
use Nette\Database\Context;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;
use Nette\SmartObject;
use Traversable;

abstract class Model
{
    use ModelConfig;
    use SmartObject {
        __call as public netteCall;
    }


    /**
     * Repository constructor.
     * @param Context $database
     */
    public function __construct(Context $database)
    {
        $this->db = $database;

        if(empty($this->table)) {
            $this->table = $this->getTable();
        }

        $this->model = $this->getModel();
    }


    protected function getTable(): string
    {
        preg_match('#(\w+)Model$#', get_class($this), $m);
        return lcfirst($m[1]);
    }


    /**
     * Specify the table associated with a model
     *
     * @param string $table
     *
     * @return Model
     */
    protected function setTable(string $table): Model
    {
        $this->table = $table;
        return $this;
    }


    protected function getModel(): Selection
    {
        return $this->db->table(($this->table));
    }


    //--------------------------------------------------------------------
    // FINDERS
    //--------------------------------------------------------------------


    /**
     * Use this method for fetching a single instance of an entity from a query.
     * This will return null if no entity was fetched.
     *  Eg: $userRepo = new UserRepository()
     *      $user = $userRepo->get(1);
     *      echo $user->name;
     * @todo doProtectFields()
     * @param int $id the primary key ID
     * @param bool $withDeleted overwrite the @var $useSoftDeleted for this query to also look at deleted fields
     * @return ActiveRow|null
     */
    public function get(int $id, $withDeleted = false): ?ActiveRow
    {
        if ($withDeleted === true) {
            return $this->getModel()->get($id);
        }
        return $this->getModel()->where($this->deletedField, 'null')->get($id);
    }


    /**
     * This method is used when you want to fetch columns by its IDs (primary key).
     * This method takes an arguments as a series of entity IDs and try to fetch them.
     *
     * @param null|int|string|array $ids
     * @param string $columns
     * @param bool $withDeleted overwrite @var $useSoftDelete for this query
     * @return Selection
     */
    public function find ($ids = null, $columns = '*', $withDeleted = false): Selection
    {
        $query = $this->getModel()
            ->select($columns)
            ->where($this->primaryKey, $ids);

        if ($withDeleted === true) {
            $query->where($this->deletedField, 'null');
        }
        return $query;

        //$eventData = $this->trigger('afterFind', ['id' => $id, 'data' => $row]);
        //return $eventData['data'];
    }


    /**
     * Find all data within the active table, may take longer time
     * if the table is enriched in rows. @Recommended to use @param $limit & @param $offset.
     *
     * @param int $limit
     * @param int $offset
     * @return Selection
     */
    public function findAll(int $limit = 0, int $offset = 0): Selection
    {
        $query = $this->getModel();
        if ($this->tempUseSoftDeletes === true)
        {
            $query->where($this->table . '.' . $this->deletedField, '');
        }
        return $query->limit($limit, $offset);
    }


    public function findColumn($column)
    {

    }


    public function findDeleted ($columns = '*'): Selection
    {
        $query = $this->getModel()
            ->select($columns)
            ->where("$this->deletedField IS NOT NULL");

        $eventData = $this->trigger('afterFind', ['columns' => $columns, 'data' => $query]);
        return $eventData['data'];
    }


    public function findOrFail()
    {

    }


    public function findOrEmpty()
    {

    }


    //--------------------------------------------------------------------
    // ADVANCED FINDERS
    //--------------------------------------------------------------------
    public function getBy($where)
    {
        return $this->getModel()->where($where)->fetch();
    }


    public function findBy($where)
    {
        return $this->getModel()->where($where);
    }


    /**
     * @param string $name
     * @param array $args
     * @return mixed
     * @todo: findBy* & getBy* dynamic calls
     */
    /*public function __call($name, $args)
    {
        
    }
    */


    //--------------------------------------------------------------------
    // MANIPULATORS
    //--------------------------------------------------------------------


    /**
     * A convenience method that will attempt to determine whether the
     * data should be inserted or updated. Will work with either
     * an array or object. When using with custom class objects,
     * you must ensure that the class will provide access to the class
     * variables, even if through a magic method.
     *
     * @param array|object $data
     *
     * @return boolean
     * @throws ModelException
     * @todo: fix & update
     */
    public function save($data): bool
    {
        if (empty($data)) {
            return true;
        }

        
    }


    /**
     * Inserts data into the current table.
     *
     * @param iterable $data
     * @param boolean $returnID Whether insert ID should be returned or not.
     *
     * @return integer|string|boolean
     * @throws ModelException
     */
    public function insert(iterable $data = null, bool $returnID = false)
    {
        // Always reset checking vars at the beginning & change it as per the event
        $this->resetCheckeingVars();

        if (empty($data)) {
            throw ModelException::forEmptyDataset(get_class($this));
        }

        if ($data instanceof Traversable) {
            $data = iterator_to_array($data);
        }

        // Must be called first so we don't strip out {timestamps}_at values.
        $data = $this->doProtectFields($data);

        // Set the timestamp behaviors if configured to do so
        if ($this->useTimestamps === true) {
            if (! empty($this->createdField) && ! array_key_exists($this->createdField, $data)) {
                $data[$this->createdField] = $this->setDateTime();
            }
            if (! empty($this->updatedField) && ! array_key_exists($this->updatedField, $data)) {
                $data[$this->updatedField] = null;
            }
            if (! empty($this->deletedField) && ! array_key_exists($this->deletedField, $data)) {
                $data[$this->deletedField] = null;
            }
        }

        $eventData = $this->trigger('beforeInsert', ['data' => $data]);

        $result = $this->getModel()->insert($eventData['data']);

        // If insertion succeeded then save the insert ID
        if ($result === false) {
            $this->noError = false;
            $this->lastError = 'Failed to save the data!';
        } else {
            $this->lastID = $result->getPrimary();
        }

        // Trigger afterInsert events with the inserted data and new ID
        $this->trigger('afterInsert', ['id' => $this->lastID, 'data' => $eventData['data'], 'result' => $result]);

        // If insertion failed, get out of here
        if (! $result) {
            return $result;
        }

        // otherwise return the insertID, if requested.
        return $returnID ? $this->lastID : $result;
    }


    /**
     * Updates records in $this->table. Meant to be used after finding
     * or getting the record;
     *
     * @param $ids
     * @param array|object $data
     *
     * @return int
     * @throws ModelException
     */
    public function update($ids, iterable $data): int
    {
        if (empty($data)) {
            //todo: add exception
            throw ModelException::forEmptyDataset('update');
        }

        if ($data instanceof Traversable) {
            $data = iterator_to_array($data);
        }

        // doProtectFields() Must be called first
        // so we don't strip out updated_at values.
        $data = $this->doProtectFields($data);

        $eventData = $this->trigger('beforeUpdate', ['id' => $ids, 'data' => $data]);

        if ($this->useTimestamps && ! empty($this->updatedField) && ! array_key_exists($this->updatedField, $data)) {
            $add = [$this->updatedField => $this->setDateTime()];
            $eventData['data'] = array_merge($eventData['data'], $add);
        }

        $result = $this->find($ids)->update($eventData['data']);

        $this->trigger('afterUpdate', ['id' => $ids, 'data' => $eventData['data'], 'result' => $result]);

        return $result;
    }


    /**
     * Deletes a single record from $this->table where $id matches
     * the table's primaryKey
     *
     * @param integer|string|array $ids The rows primary key(s)
     * @param bool $completely allows overriding the soft deletes setting.
     *
     * @return int number of deleted (soft/complete) rows
     * @throws ModelException
     */
    public function delete($ids, bool $completely = false): int
    {
        $this->trigger('beforeDelete', ['id' => $ids, 'completely' => $completely, 'result' => null, 'data' => null]);

        if ($this->useSoftDeletes && ! $completely) {
            $set[$this->deletedField] = $this->setDateTime();
            if (! empty($this->updatedField)) {
                $set[$this->updatedField] = $this->setDateTime();
            }
            $result = $this->update($ids, $set);
        } else {
            // Or delete permanently
            $result = $this->find($ids)->delete();
        }

        $this->trigger('afterDelete', ['id' => $ids, 'completely' => $completely, 'result' => $result, 'data' => null]);

        return $result;
    }


    /**
     * Permanently deletes all rows that have been marked as deleted
     * through soft deletes (deleted_at != null)
     *
     * @return int
     */
    public function removeDeleted(): int
    {
        if (! $this->useSoftDeletes) {
            return 0;
        }
        return $this->findAll()
            ->where("$this->deletedField IS NOT NULL")
            ->delete();
    }


    //--------------------------------------------------------------------
    // UTILITIES
    //--------------------------------------------------------------------


    /**
     * Ensures that only the fields that are allowed to be updated
     * are in the data array.
     *
     * Used by insert() and update() to protect against mass assignment
     * vulnerabilities.
     *
     * @param array $data
     *
     * @return array
     * @throws ModelException
     */
    protected function doProtectFields(array $data): array
    {
        if ($this->protectFields === false) {
            return $data;
        }

        if (empty($this->allowedFields)) {
            throw ModelException::forInvalidAllowedFields(get_class($this));
        }

        if (is_array($data) && count($data)) {
            foreach ($data as $key => $val) {
                if (is_array($data) && count($data)) {
                    $this->doProtectFields($data);
                }
                if (!in_array($key, $this->allowedFields, true)) {
                    unset($data[$key]);
                }
            }
        }

        return $data;
    }


    /**
     * A utility function to allow child models to use the type of
     * date/time format that they prefer. This is primarily used for
     * setting created_at, updated_at and deleted_at values, but can be
     * used by inheriting classes.
     *
     * The available time formats are:
     *  - 'int'      - Stores the date as an integer timestamp
     *  - 'datetime' - Stores the data in the SQL datetime format
     *  - 'date'     - Stores the date (only) in the SQL date format.
     *
     * @param integer $userData An optional PHP timestamp to be converted.
     *
     * @return mixed
     * @throws ModelException
     */
    protected function setDateTime(int $userData = null)
    {
        $currentDate = is_numeric($userData) ? (int) $userData : time();
        switch ($this->dateFormat) {
            case 'int':
                return $currentDate;
            case 'datetime':
                return date('Y-m-d H:i:s', $currentDate);
            case 'date':
                return date('Y-m-d', $currentDate);
            default:
                throw ModelException::forNoDateFormat(get_class($this));
        }
    }


    /**
     * A simple event trigger for Model Events that allows additional
     * data manipulation within the model. Specifically intended for
     * usage by child models this can be used to format data,
     * save/load related classes, etc.
     *
     * It is the responsibility of the callback methods to return
     * the data itself.
     *
     * Each $eventData array MUST have a 'data' key with the relevant
     * data for callback methods (like an array of key/value pairs to insert
     * or update, an array of results, etc)
     *
     * @param string $event
     * @param array  $eventData
     *
     * @return mixed
     * @todo: complete the simple event system
     */
    //todo: fix TRIGGER
    protected function trigger(string $event, array $eventData)
    {
        // Ensure it's a valid event
        if (! isset($this->{$event}) || empty($this->{$event}))
        {
            return $eventData;
        }

        foreach ($this->{$event} as $callback)
        {
            if (! method_exists($this, $callback))
            {
                //throw ModelException::forInvalidMethodTriggered($callback);
            }

            $eventData = $this->{$callback}($eventData);
        }

        return $eventData;
    }


    /**
     * This method resets the checking vars to it's default value
     * Those vars are meant to store only the last action specific values.
     *
     * These are the vars that you are going to use to determine whether
     * the action is succeeded or failed. Even can set/have a message.
     *
     * It should be used every time at the beginning if you are going to depend
     * on those for checking whether the last action was succeeded or not.
     */
    protected function resetCheckeingVars()
    {
        // Initializing the checking vars
        // These should change upon event result.
        $this->lastID = 0;
        $this->noError = true;
        $this->lastError = null;
        $this->lastMessage = null;
    }

    /**
     * Is there any error? This is a syntactic sugar for $noError
     *      if($this->repo->anyError) return false;
     *      else return true;
     * It's that simple.
     *
     * @return bool
     */
    public function anyError(): bool
    {
        return ! $this->noError;
    }


    public function getError(): ?string
    {
        return $this->lastError;
    }


    public function setError($msg = 'Error while operating with database! (E1001)'): void
    {
        $this->noError = false;
        $this->lastError = $msg;
    }

    public function setWarning($msg, $isCritical = false): void
    {
        $this->noError = $isCritical;
        $this->lastMessage = $msg;
    }

}
