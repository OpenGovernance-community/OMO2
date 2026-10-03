<?php
namespace dbObject;

final class ObjectMailRecipient extends DbObject
{
    public static function tableName() { return 'object_mail_recipient'; }
    public static function rules() { return [
        [['IDobject_mail', 'member_id', 'email', 'status', 'updated_at'], 'required'],
        [['id', 'updated_at'], 'integer'], [['IDobject_mail'], 'fk'],
        [['member_id', 'email', 'status'], 'string'], [['id'], 'safe']]; }
    public static function attributeLabels() { return ['IDobject_mail' => 'Message', 'member_id' => 'Membre',
        'email' => 'Adresse', 'status' => 'Statut', 'updated_at' => 'Mise a jour']; }
    public static function attributeLength() { return ['member_id' => 80, 'email' => 254, 'status' => 20]; }
    public function canView() { $mail = new ObjectMail(); return $mail->load((int)$this->get('IDobject_mail')) && $mail->canView(); }
    public function canViewDetail() { return $this->canView(); }
    public function canEdit() { return false; }
}
