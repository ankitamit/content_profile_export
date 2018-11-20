<?php
namespace Drupal\content_profile_export\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\profile\Entity\Profile;
use Drupal\user\Entity\User;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Utility\Error;


class ContentProfileFieldsExport extends ControllerBase {


	public function getFieldsByKeys($value) {
		return strpos($value,"field_") === 0;
	}


	public function generateFields() {


                $configObjetContentProfile = \Drupal::config('content_profile_export.settings');
                $contentTypeName = $configObjetContentProfile->get('content_type_name');
                $profileTypeName = $configObjetContentProfile->get('profile_type_name');


		$bundle_fields_object = \Drupal::getContainer()->get('entity_field.manager');

		$bundle_fields_object->clearCachedFieldDefinitions();

		$bundle_fields = $bundle_fields_object->getFieldDefinitions('node', $contentTypeName);

		$bundle_fieldsKeys = array_keys($bundle_fields);

		$fieldNamesToExport = array_values(array_filter($bundle_fieldsKeys, array($this,"getFieldsByKeys")));


		$getObjectField = \Drupal::entityTypeManager()->getStorage('field_storage_config');
		$getObjectFieldInstance =  \Drupal::entityTypeManager()->getStorage('field_config');

		foreach($fieldNamesToExport as $value) {

			$field_definition = $bundle_fields[$value]->toArray();
			$field =  $getObjectField->load("node.$value")->toArray();


			foreach(array('uuid','id','dependencies') as $internalKeys) {
				unset($field[$internalKeys]);
				unset($field_definition[$internalKeys]);
			}


			$field['entity_type'] = 'profile';
			$field['id'] = $value;
			$field['field_name'] = $value;


			$field_definition['id'] = $value;
			$field_definition['entity_type'] = 'profile';
			$field_definition['bundle'] = $profileTypeName;
			$field_definition['field_name'] = $value;

			if(isset($field_definition['settings']['on_label']) && $field_definition['settings']['on_label'] == 'On')
				$field_definition['settings']['on_label'] = $field_definition['label'];




                         //Getting the existing Field If exists
			$getExistingFieldStorage = $getObjectField->load('profile'.'.'. $id);
			if(empty($getExistingFieldStorage)) {
				try {
					FieldStorageConfig::create($field)->save();
				} catch(\Exception $exception) {
                                        \Drupal::logger('content_profile_export_create_error')->notice($exception);
				}
			}
                        else
                          \Drupal::logger('content_profile_export')->notice("Field Storage profile.$id already exists");



                        
                        //Getting the existing Instance of the Field in Bundle
			$getExistingInstanceBundle = $getObjectFieldInstance->load('profile'.'.'.$profileTypeName.'.'.$id);

	                if(empty($getExistingInstanceBundle)) {
                                 try {
					FieldConfig::create($field_definition)->save();
			         } catch(\Exception $exception) {
                                 \Drupal::logger('content_profile_export_instance_error')->notice($exception); 
			         }
                        }
                        else
                           \Drupal::logger('content_profile_export')->notice("Field Instance 'profile.$profileTypeName.$id' already exists");
                       

		}



	}




  public function convertFieldType() {


                $getObjectField = \Drupal::entityTypeManager()->getStorage('field_storage_config');
                $getObjectFieldInstance =  \Drupal::entityTypeManager()->getStorage('field_config');

 $loadField =  \Drupal\field\Entity\FieldStorageConfig::loadByName('node', 'field_constcoordinator_member');


 $loadInstance = $getObjectFieldInstance->load('node.elections.field_bliptv');
echo ' **load field is **';
print_r($loadField);

echo ' ** instance load is ***';
print_r($loadInstance)."\n\n";
die;



               $contentTypeName = 'productioncalls';

                $getMigrationDBObject =   \Drupal\Core\Database\Database::getConnection('default', 'migrate');

               $sqlQueryMigration = "select field_name,label, widget_type,widget_module, widget_settings,description  from content_node_field_instance where widget_type = 'text_textfield' and type_name = :typeName";


              $queryObject = $getMigrationDBObject->query($sqlQueryMigration,array(':typeName'=>$contentTypeName));

               while($getTextFields = $queryObject->fetchAssoc()) {

                $getTextFields['widget_settings'] = unserialize($getTextFields['widget_settings']);
                $getSizeTextField = $getTextFields['widget_settings']['size'];


$fieldName = $getTextFields['field_name'];

if($fieldName == 'field_productionofficepostalcode')
  continue;

 try {


       // FieldConfig::loadByName('node', $contentTypeName, $getTextFields['field_name'])->delete();

      
      
       $fieldStorageConfig =  FieldStorageConfig::loadByName('node', $getTextFields['field_name']);
       if($fieldStorageConfig)
        $fieldStorageConfig->delete();
}
  catch(\Exception $e) { 
  echo ' ** here excpetion is ***'.$e->getMessage()."\n\n";
  }

echo ' ** doing for field name **'.$fieldName."\n\n";
   


FieldStorageConfig::create(array(
  'field_name' => $getTextFields['field_name'],
  'entity_type' => 'node',
  'type' => 'string',
  'cardinality' => 1,
  'settings'=>array('max_length'=>'255'),
))->save();



FieldConfig::create([
  'field_name' => $getTextFields['field_name'],
  'entity_type' => 'node',
  'bundle' => $contentTypeName,
  'label' => $getTextFields['label'],
  'description'=>$getTextFields['description']
])->save();


echo ' **created fro field name ***'.$fieldName."\n\n\n";

}

echo ' ** done here **';
die;
 
}





} 
