<?php

namespace modules\catalog\models;

use modules\landings\models\Field;
use Yii;
use yii\db\ActiveRecord;
use common\components\MultipleModel;
use common\behaviors\TimestampBehavior;
use common\behaviors\ImageBehavior;
use common\behaviors\I18nBehavior;
use common\behaviors\SortingBehavior;
use common\behaviors\CreatorBehavior;
use common\behaviors\ReadOnlyBehavior;
use common\models\User;

/**
 * This is the model class for table "{{%ctlg_product}}".
 *
 * @property integer $id
 * @property integer $parent_id
 * @property integer $user_id
 * @property string $name
 * @property string $slug
 * @property string $image
 * @property double $price
 * @property string $description
 * @property integer $visible
 * @property string $sorting
 * @property integer $created_at
 * @property integer $updated_at
 *
 * @property Category $parent
 * @property User $user
 * @property ProductI18n[] $productI18ns
 * @property Language[] $languages
 */
class Product extends ActiveRecord
{
    const VISIBLE_NO = 0;
    const VISIBLE_YES = 1;

    const IMAGE_EXTENSIONS = 'png, jpg, jpeg, gif';
    const NAME_MAX_LENGTH = 100;

    public $additionalFields = [];

    /**
     * @return array
     */
    public static function getVisibilityStatuses()
    {
        return [
            self::VISIBLE_NO => Yii::t('app', 'No'),
            self::VISIBLE_YES => Yii::t('app', 'Yes'),
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%ctlg_product}}';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
            CreatorBehavior::className(),
            SortingBehavior::className(),
            ImageBehavior::className(),
            ReadOnlyBehavior::className(),
            [
                'class'=> I18nBehavior::className(),
                'i18nModelClass' => ProductI18n::className(),
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['parent_id', 'user_id', 'sorting', 'created_at', 'updated_at'], 'integer'],
            [['name', 'slug'], 'required'],
            [['price'], 'number'],
            [['description'], 'string'],
            [['name', 'slug', 'producer'], 'string', 'max' => self::NAME_MAX_LENGTH],
            [['visible'], 'boolean'],
            [['image'], 'file', 'skipOnEmpty' => true, 'extensions' => self::IMAGE_EXTENSIONS],
            [['slug'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'parent_id' => Yii::t('app', 'Category'),
            'user_id' => Yii::t('app', 'Owner'),
            'name' => Yii::t('app', 'Name'),
            'slug' => Yii::t('app', 'Slug'),
            'producer' => Yii::t('app', 'Producer'),
            'image' => Yii::t('app', 'Image'),
            'price' => Yii::t('app', 'Price'),
            'description' => Yii::t('app', 'Description'),
            'visible' => Yii::t('app', 'Visible'),
            'sorting' => Yii::t('app', 'Sorting'),
            'created_at' => Yii::t('app', 'Created'),
            'updated_at' => Yii::t('app', 'Updated'),
            'created' => Yii::t('app', 'Created'),
            'updated' => Yii::t('app', 'Updated'),
        ];
    }

    /**
     * @param $fieldId
     * @return array|null|ActiveRecord
     */
    private function getProductFieldById($fieldId)
    {
        return ProductField::find()
            ->where([
                'product_id' => $this->id,
                'field_id' => $fieldId,
            ])
            ->one();
    }

    /**
     * @return mixed
     */
    public function afterFind()
    {
        if (empty($this->parent) || empty($this->parent->fields)) {
            return parent::afterFind();
        }

        foreach ($this->parent->fields as $field) {
            $productField = $this->getProductFieldById($field->id);

            if (empty($productField)) {
                $productField = new ProductField();
                $productField->field_id = $field->id;
            }

            $this->additionalFields[] = $productField;
        }

        return parent::afterFind();
    }

    /**
     * @param boolean $insert
     * @param mixed $changedAttributes
     * @return boolean
     */
    public function afterSave($insert, $changedAttributes)
    {
        $productFields = MultipleModel::createMultiple(ProductField::className(), $this->getProductFields());
        MultipleModel::loadMultiple($productFields, Yii::$app->request->post());

        /** @var Field $field */
        foreach ($productFields as $field) {
            $field->product_id = $this->id;
            $field->save(false);
        }

        return parent::afterSave($insert, $changedAttributes);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParent()
    {
        return $this->hasOne(Category::className(), ['id' => 'parent_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProductFields()
    {
        return $this->hasMany(ProductField::className(), ['product_id' => 'id']);
    }

    /**
     * @param bool $active
     * @return bool
     */
    public function setActive($active = true)
    {
        $this->visible = $active;
        return $this::save(false);
    }

    /**
     * @return boolean
     */
    public function reverseActive()
    {
        return $this->setActive(!$this->visible);
    }

    /**
     * @param int $parentId
     * @return array
     * @throws \yii\base\InvalidConfigException
     */
    public static function getFields($parentId = null)
    {
        $columns = self::getTableSchema()->getColumnNames();
        $fields = [];

        foreach ($columns as $column) {
            $attributeLabels = self::attributeLabels();
            $fields[$column] = !empty($attributeLabels[$column]) ? $attributeLabels[$column] : $column;
        }

        if (empty($parentId)) {
            return $fields;
        }

        $category = Category::findOne($parentId);

        if (!empty($category)) {
            /** @var Field $field */
            foreach ($category->fields as $field) {
                $fields[$field->id] = $field->name;
            }
        }

        return $fields;
    }
}
