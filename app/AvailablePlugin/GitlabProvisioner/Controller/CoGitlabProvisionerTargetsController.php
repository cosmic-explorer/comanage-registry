<?php
/**
 * COmanage Registry CO GitLab Provisioner Targets Controller
 *
 * Portions licensed to the University Corporation for Advanced Internet
 * Development, Inc. ("UCAID") under one or more contributor license agreements.
 * See the NOTICE file distributed with this work for additional information
 * regarding copyright ownership.
 *
 * UCAID licenses this file to you under the Apache License, Version 2.0
 * (the "License"); you may not use this file except in compliance with the
 * License. You may obtain a copy of the License at:
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * 
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.9.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("SPTController", "Controller");

// This file is generated by Composer
require_once APP . "AvailablePlugin" . DS . "GitlabProvisioner" . DS . "Vendor" . DS . "autoload.php";

class CoGitlabProvisionerTargetsController extends SPTController {
  // Class name, used by Cake
  public $name = "CoGitlabProvisionerTargets";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'gitlab_user' => 'asc'
    )
  );
  
  /**
   * Accept a callback from GitLab.
   *
   * @since  COmanage Registry v0.9.1
   * @param  integer CoGitlabProvisioner ID
   */
  
  public function callback($id) {
    if(!empty($this->request->query['code'])
       && !empty($this->request->query['state'])) {
      // Cross check the code against 
      
      $state = $this->Session->read('Plugin.GitlabProvisioner.state');
      
      if($state == $this->request->query['state']) {
        // Need to pull the current values
        
        $args = array();
        $args['conditions']['CoGitlabProvisionerTarget.id'] = $id;
        $args['contain'] = false;
        
        $curdata = $this->CoGitlabProvisionerTarget->find('first', $args);
        
        if(!empty($curdata)) {
          // No need to use the cached client here
          $client = new GuzzleHttp\Client(['base_uri' => 'https://gitlab.com']);
          
          $response = $client->request('POST', '/oauth/token',
            [
              'form_params' => [
                'client_id'     => $curdata['CoGitlabProvisionerTarget']['client_id'],
                'client_secret' => $curdata['CoGitlabProvisionerTarget']['client_secret'],
                'code'          => $this->request->query['code'],
                'grant_type'    => 'authorization_code',
                'redirect_uri'  => Router::url(null, true)
              ],
              'headers' => [
                'Accept'        => 'application/json'
              ]
            ]
          );
          
          $json = json_decode($response->getBody()->getContents(), true);
          
          if(!empty($json['access_token'])) {
            $this->CoGitlabProvisionerTarget->id = $id;
            
            if($this->CoGitlabProvisionerTarget->saveField('access_token', $json['access_token'])) {
              // Redirect to select org to manage if one is not already set.
              if(empty($curdata['CoGitlabProvisionerTarget']['gitlab_org'])) {
                $target = array();
                $target['plugin'] = 'gitlab_provisioner';
                $target['controller'] = 'co_gitlab_provisioner_targets';
                $target['action'] = 'select';
                $target[] = $id;
                
                $this->redirect($target);
              }
            } else {
              $this->Flash->set(_txt('er.db.save'), array('key' => 'error'));
            }
          } else {
            $this->Flash->set(_txt('er.gitlabprovisioner.access_token'), array('key' => 'error'));
          }
        } else {
          $this->Flash->set(_txt('er.notfound', array(_txt('ct.co_gitlab_provisioner_targets.1'), filter_var($id,FILTER_SANITIZE_SPECIAL_CHARS))),
                            array('key' => 'error'));
        }
      } else {
        $this->Flash->set(_txt('er.gitlabprovisioner.state'), array('key' => 'error'));
      }
    }
    
    $this->performRedirect();
  }
  
  /**
   * Update a CO GitlabProvisioner Target.
   * - precondition: Model specific attributes in $this->request->data (optional)
   * - precondition: <id> must exist
   * - postcondition: On GET, $<object>s set
   * - postcondition: On POST success, object updated
   * - postcondition: On POST, session flash message updated
   *
   * @since  COmanage Registry v0.9.1
   * @param  integer CoGitlabProvisioner ID
   */
  
  public function edit($id) {
    parent::edit($id);
    
    // Set the callback URL
    $this->set('vv_gitlab_callback_url', array('plugin'     => 'gitlab_provisioner',
                                               'controller' => 'co_gitlab_provisioner_targets',
                                               'action'     => 'callback',
                                               $id));
    
    // Determine if the 'GitLab' type has been configured
    
    $types = $this->CoGitlabProvisionerTarget->CoProvisioningTarget->Co->CoPerson->Identifier->types($this->cur_co['Co']['id'], 'type');
    
    // Pass a hint to the view regarding the gitlab type
    $this->set('vv_gitlab_type', in_array('GitLab', array_keys($types)));
    
    $this->set('vv_extended_type_url', array('plugin'     => null,
                                             'controller' => 'co_extended_types',
                                             'action'     => 'index',
                                             'co'         => $this->cur_co['Co']['id'],
                                             'attr'       => 'Identifier'));
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v0.9.1
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Accept a callback from GitLab?
    $p['callback'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Delete an existing CO Provisioning Target?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Edit an existing CO Provisioning Target?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View all existing CO Provisioning Targets?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Select a GitLab Organization to manage?
    $p['select'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View an existing CO Provisioning Target?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    $this->set('permissions', $p);
    return($p[$this->action]);
  }
  
  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v0.9.1
   */
  
  public function performRedirect() {
    if($this->action == 'edit'
       && $this->request->is(array('post', 'put'))
       && !empty($this->viewVars['co_gitlab_provisioner_targets'][0]['CoGitlabProvisionerTarget']['client_id'])) {
      // This is a save operation, so get a (new) access token
      
      $scope = $this->CoGitlabProvisionerTarget->calculateScope($this->viewVars['co_gitlab_provisioner_targets'][0]);
      $state = Security::generateAuthKey();
      
      // Stuff the state key into the session so we can compare it on callback
      $this->Session->write('Plugin.GitlabProvisioner.state', $state);
      
      $querystr = 'client_id=' . urlencode($this->viewVars['co_gitlab_provisioner_targets'][0]['CoGitlabProvisionerTarget']['client_id'])
                  . '&redirect_uri=' . urlencode(Router::url(array('plugin'     => 'gitlab_provisioner',
                                                                   'controller' => 'co_gitlab_provisioner_targets',
                                                                   'action'     => 'callback',
                                                                   $this->viewVars['co_gitlab_provisioner_targets'][0]['CoGitlabProvisionerTarget']['id']
                                                                  ), true))
                  . '&response_type=code'
                  . '&scope=' . urlencode($scope)
                  . '&state=' . urlencode($state);
      
      $this->redirect('https://gitlab.com/oauth/authorize?' . htmlentities($querystr));
    }
    
    parent::performRedirect();
  }
  
  /**
   * Select a GitLab Organization to manage.
   *
   * @since  COmanage Registry v0.9.1
   * @param  integer CoGitlabProvisioner ID
   */
  
  function select($id=null) {
    if($this->request->is('get')) {
      $args = array();
      $args['conditions']['CoGitlabProvisionerTarget.id'] = $id;
      $args['contain'] = false;
      
      $curdata = $this->CoGitlabProvisionerTarget->find('first', $args);
      
      if(!empty($curdata)
         && !empty($curdata['CoGitlabProvisionerTarget']['access_token'])) {
        try {
          // Determine which organizations we could potentially manage
          
          $ownedOrgs = $this->CoGitlabProvisionerTarget->ownedOrganizations($curdata['CoGitlabProvisionerTarget']['access_token'],
                                                                            $curdata['CoGitlabProvisionerTarget']['gitlab_user']);
          
          if(!empty($ownedOrgs)) {
            $this->set('vv_co_gitlab_provisioner_target', $curdata);
            $this->set('vv_owned_gitlab_orgs', $ownedOrgs);
            $this->set('title_for_layout', _txt('pl.gitlabprovisioner.org.select'));
          } else {
            $this->Flash->set(_txt('er.gitlabprovisioner.orgs.none'), array('key' => 'error'));
          }
        }
        catch(Exception $e) {
          $this->Flash->set($e->getMessage(), array('key' => 'error'));
        }
      } else {
        $this->Flash->set(_txt('er.notfound', array(_txt('ct.co_gitlab_provisioner_targets.1'), filter_var($id,FILTER_SANITIZE_SPECIAL_CHARS))),
                          array('key' => 'error'));
      }
    } else {
      // Save the field and redirect
      
      $this->CoGitlabProvisionerTarget->id = $this->request->data['CoGitlabProvisionerTarget']['id'];
      
      if($this->CoGitlabProvisionerTarget->saveField('gitlab_org', $this->request->data['CoGitlabProvisionerTarget']['gitlab_org'])) {
        $this->Flash->set(_txt('pl.gitlabprovisioner.token.ok'), array('key' => 'success'));
      } else {
        $this->Flash->set(_txt('er.db.save'), array('key' => 'error'));
      }
      
      $this->performRedirect();
    }
  }
}
