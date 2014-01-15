<?php
/**
 * Renders a multiple information 
 * 
 * @package sl
 * @subpackage dashboard
 */
class modDashboardWidgetResume extends modDashboardWidgetInterface {

    /*
     * Résumé de packages
     */
    public function resumePackage() {
        $query = $this->modx->newQuery('transport.modTransportPackage');
        $components = $this->modx->getIterator('transport.modTransportPackage', $query);
        $i = 0;
        foreach ($components as $component) {
            $c['class'] = ($i++ % 2 == 0) ? 'x-grid3-row-alt' : '';
            $c['name'] = $component->package_name;
            $c['version'] = $component->getComparableVersion();
            $c['install'] = empty($component->installed) ? 'Not installed' : $component->installed;
            $o[] = $this->getFileChunk('dashboard/resumePackageItem.tpl',$c);
        }
        return implode("\n",$o);
    }

    /*
     * resumé des resource
     */
    public function resumeResource($aModels) {
        $i = 0;
        foreach ($aModels as $model) {
            $e['model'] = $model;
            $e['class'] = ($i++ % 2 == 0) ? 'x-grid3-row-alt' : '';
            // total
            $query = $this->modx->newQuery('mod'.$model);
            if ($model != 'Resource') {
                $query->where(array('class_key' => 'mod'.$model));
            }
            $e['total'] = $this->modx->getCount('mod'.$model, $query);

            // Published
            $query = $this->modx->newQuery('mod'.$model);
            if ($model != 'Resource') {
                $query->where(array('class_key' => 'mod'.$model));
            }
            $query->where(array('published' => 1));
            $e['published'] = $this->modx->getCount('mod'.$model, $query);

            // Deleted
            $query = $this->modx->newQuery('mod'.$model);
            if ($model != 'Resource') {
                $query->where(array('class_key' => 'mod'.$model));
            }
            $query->where(array('deleted' => 1));
            $e['deleted'] = $this->modx->getCount('mod'.$model, $query);

            $o[] =  $this->getFileChunk('dashboard/resumeResourceItem.tpl',$e);
        }
        return implode("\n",$o);
    }

    /*
     * resumé des élements
     */
    public function resumeElement($aModels) {
        $i = 0;
        foreach ($aModels as $model) {
            $e['model'] = $model;
            $e['class'] = ($i++ % 2 == 0) ? 'x-grid3-row-alt' : '';
            $query = $this->modx->newQuery('mod'.$model);
            $e['total'] = $this->modx->getCount('mod'.$model, $query);
            $o[] =  $this->getFileChunk('dashboard/resumeElementItem.tpl',$e);
        }
        return implode("\n",$o);
    }

    /*
     * Résumpé de la partie Utilisateur
     */
    public function resumeUsers() {
       $query = $this->modx->newQuery('modUserGroup');
       $userGroupCollection = $this->modx->getIterator('modUserGroup', $query);
       $e['total'] = $this->modx->getCount('mod'.$model, $query);

       foreach ($userGroupCollection as $userGroup) {
           $e['name'] =  $userGroup->get('name');

           // total users
           $query = $this->modx->newQuery('modUserGroupMember');
           $query->where(array(
               'modUserGroupMember.user_group' => $userGroup->get('id')
           ));
           $e['total'] = $this->modx->getCount('modUserGroupMember', $query);

           // activ users
           $query = $this->modx->newQuery('modUserGroupMember');
           $query->innerJoin('modUser', 'User', '`modUserGroupMember`.`member` = `User`.`id`' );
           $query->where(array(
               'modUserGroupMember.user_group' => $userGroup->get('id'),
               'User.active' => 1
           ));
           $e['active'] = $this->modx->getCount('modUserGroupMember', $query);

           // blocked users
           $query = $this->modx->newQuery('modUserGroupMember');
           //$query->innerJoin('modUser', 'User', '`modUserGroupMember`.`member` = `User`.`id`' );
           $query->innerJoin('modUserProfile', 'Profile','`modUserGroupMember`.`member` = `Profile`.`id`' );
           $query->where(array(
               'modUserGroupMember.user_group' => $userGroup->get('id'),
               'Profile.blocked' => 1
           ));
           $e['blocked'] = $this->modx->getCount('modUserGroupMember', $query);

           $o[] =  $this->getFileChunk('dashboard/resumeUserItem.tpl',$e);
        }

        $e['name'] = 'Utilisateurs';

        $query = $this->modx->newQuery('modUser');
        $e['total'] = $this->modx->getCount('modUser', $query);

        //$query = $this->modx->newQuery('modUser','User');
        $query->where(array('active' => '1'));
        $e['active'] = $this->modx->getCount('modUser', $query);

        $query = $this->modx->newQuery('modUser');
        $query->innerJoin('modUserProfile','Profile', '`modUser`.`id`=`Profile`.`id`');
        $query->where(array('Profile.blocked' => 1));
        $e['blocked'] = $this->modx->getCount('modUser', $query);
        $o[] =  $this->getFileChunk('dashboard/resumeUserItem.tpl',$e);
        return implode("\n",$o);
    }

    public function render() {

        $resume['titlePackage'] = 'Liste des composants';
        $resume['itemsPackage'] = $this->resumePackage();

        $aResources = array('Resource', 'Document', 'StaticResource', 'WebLink', 'SymLink');
        $resume['titleResource'] = 'Les resources';
        $resume['itemsResource'] = $this->resumeResource($aResources);

        $aElements = array('Template', 'Snippet', 'Chunk', 'TemplateVar', 'Category', 'Plugin', 'Context');
        $resume['titleElement'] = 'Les éléments';
        $resume['itemsElement'] = $this->resumeElement($aElements);

        $resume['titleUser'] = 'Les utilisateurs et leur groupes';
        $resume['itemsUser'] = $this->resumeUsers();

        $package = $this->getFileChunk('dashboard/resumePackageOuter.tpl', $resume);
        $element = $this->getFileChunk('dashboard/resumeElementOuter.tpl', $resume);
        $resource = $this->getFileChunk('dashboard/resumeResourceOuter.tpl', $resume);
        $user = $this->getFileChunk('dashboard/resumeUserOuter.tpl', $resume);

        $this->modx->controller->addHTML(
            '<script type="text/javascript">
            Ext.onReady(function(){
                // basic tabs 1, built from existing content
                var tabs = new Ext.TabPanel({
                    renderTo: "resume",
                    enableTabScroll:true,
                    activeTab: 0,
                    frame:false,
                    plain:true,
                    defaults:{autoHeight: true, autoScroll:true},
                    items:[
                        {
                            title: "Liste des composants",
                            html:\''.str_replace("\n", "", $package).'\',
                        },{
                            title: "Les resources",
                            html:\''.str_replace("\n", "", $resource).'\',
                        },{
                            title: "Les éléments",
                            html:\''.str_replace("\n", "", $element).'\',
                        },{
                            title: "Les utilisateurs et leur groupes",
                            html:\''.str_replace("\n", "", $user).'\',
                        }
                    ]
                });
            });
            </script>'
        );
        return $this->getFileChunk('dashboard/resume.tpl');
    }
}
return 'modDashboardWidgetResume';


