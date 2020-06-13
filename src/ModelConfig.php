<?php

declare(strict_types=1);

namespace Farhanianz\NetteModel;

use Nette\Database\Context;

trait ModelConfig
{
    /**
     * @var Context Nette database explorer
     */
    protected $db;

    /**
     * Name of database table
     *
     * @var string
     */
    protected $table;


    /**
     * @var Context active table model
     */
    protected $model;

    /**
     * The table's primary key.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Whether we should limit fields in inserts
     * and updates to those available in $allowedFields or not.
     *
     * @var boolean
     */
    protected $protectFields = false;

    /**
     * An array of field names that are allowed
     * to be set by the user in inserts/updates.
     *
     * @var array
     */
    protected $allowedFields = [];

    /**
     * The column used for insert timestamps
     *
     * @var string
     */
    protected $createdField = 'created_at';

    /**
     * The column used for update timestamps
     *
     * @var string
     */
    protected $updatedField = 'updated_at';

    /**
     * The column used to save soft delete state
     *
     * @var string
     */
    protected $deletedField = 'deleted_at';

    /**
     * If this model should use "softDeletes" and
     * simply set a date when rows are deleted, or
     * do hard deletes.
     *
     * @var boolean
     */
    protected $useSoftDeletes = true;

    /**
     * If true, will set created_at, and updated_at
     * values during insert and update routines.
     *
     * @var boolean
     */
    protected $useTimestamps = true;

    /**
     * The type of column that created_at and updated_at
     * are expected to.
     *
     * Allowed: 'datetime', 'date', 'int'
     *
     * @var string
     */
    protected $dateFormat = 'datetime';

    /**
     * The format that the results should be returned as.
     * Will be overridden if the as* methods are used.
     *
     * @var string
     */
    protected $returnType = 'array';

    /**
     * Last insert/update ID
     *
     * @var integer
     */
    protected $lastID = 0;

    /**
     * Last fetched row
     *
     * @var mixed
     */
    protected $lastData = null;

    /**
     * Is there any error?
     *
     * @var bool
     */
    public $noError = true;

    /**
     * Last error (null or string containing error message)
     *
     * @var null|string
     */
    public $lastError = null;

    /**
     * Last message (false or string containing a message)
     * In this way you can determine whether it's an error or warning.
     *
     * Succeeded/skipped the action? so get a warning/notice message
     * or the reason of skipping the action.
     *
     * Imagine a situation where you want to save tags only once, when you want
     * to save it you would check if it already exists, if it does than don't save again.
     * Just set a message here & stop execution. Later check if there's a message or not
     * after/before checking there's an error message.
     *
     * @var null|string
     */
    public $lastMessage = null;

    //--------------------------------------------------------------------


    /*
     * Callbacks. Each array should contain the method
     * names (within the model) that should be called
     * when those events are triggered. With the exception
     * of 'afterFind', all methods are passed the same
     * items that are given to the update/insert method.
     * 'afterFind' will also include the results that were found.
     */


    /**
     * Callbacks for beforeInsert
     *
     * @var
     */
    protected $beforeInsert = [];
    /**
     * Callbacks for afterInsert
     *
     * @var
     */
    protected $afterInsert = [];
    /**
     * Callbacks for beforeUpdate
     *
     * @var
     */
    protected $beforeUpdate = [];
    /**
     * Callbacks for afterUpdate
     *
     * @var
     */
    protected $afterUpdate = [];
    /**
     * Callbacks for afterFind
     *
     * @var
     */
    protected $afterFind = [];
    /**
     * Callbacks for beforeDelete
     *
     * @var
     */
    protected $beforeDelete = [];
    /**
     * Callbacks for afterDelete
     *
     * @var
     */
    protected $afterDelete = [];
    

}
