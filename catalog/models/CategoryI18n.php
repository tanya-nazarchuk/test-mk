<?php

namespace modules\catalog\models;

use Yii;
use yii\db\ActiveRecord;
use modules\i18n\models\Language;

/**
 * This is the model class for table "{{%catalog_i18n}}".
 *
 * @property integer $id
 * @property string $language
 * @property string $name
 * @property string $description
 *
 * @property Language $language0
 * @property Category $id0
 */
class CategoryI18n extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%ctlg_category_i18n}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'language'], 'required'],
            [['id'], 'integer'],
            [['description'], 'string'],
            [['language'], 'string', 'max' => 5],
            [['name'], 'string', 'max' => 100],
            [['language'], 'exist', 'skipOnError' => true, 'targetClass' => Language::className(), 'targetAttribute' => ['language' => 'language']],
            [['id'], 'exist', 'skipOnError' => true, 'targetClass' => Category::className(), 'targetAttribute' => ['id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'language' => Yii::t('app', 'Language'),
            'name' => Yii::t('app', 'Name'),
            'description' => Yii::t('app', 'Description'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLanguage()
    {
        return $this->hasOne(Language::className(), ['language' => 'language']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCategory()
    {
        return $this->hasOne(Category::className(), ['id' => 'id']);
    }
}
