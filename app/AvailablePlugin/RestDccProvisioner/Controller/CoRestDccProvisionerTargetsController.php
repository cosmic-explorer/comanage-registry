<?php
/**
 * COmanage Registry CO RestDcc Provisioner Targets Controller
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
 * @since         COmanage Registry v3.2.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("SPTController", "Controller");

// This file is generated by Composer
require_once APP . "AvailablePlugin" . DS . "RestDccProvisioner" . DS . "Vendor" . DS . "autoload.php";

class CoRestDccProvisionerTargetsController extends SPTController {
  // Class name, used by Cake
  public $name = "CoRestDccProvisionerTargets";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'restdcc_url' => 'asc'
    )
  );
  
  /**
   * Accept a callback from the DCC's Hydra.
   *
   * @since  COmanage Registry v3.2.3
   * @param  integer CoRestDccProvisioner ID
   */
  
  public function callback($id) {
    if(!empty($this->request->query['code'])
       && !empty($this->request->query['state'])) {

      // Cross check the state against the session state
      $state = $this->Session->read('Plugin.RestDccProvisioner.state');

      if($state == $this->request->query['state']) {
        $redirect_uri = $this->Session->read('Plugin.RestDccProvisioner.redirect_uri');

        // Need to pull the current values
        $args = array();
        $args['conditions']['CoRestDccProvisionerTarget.id'] = $id;
        $args['contain'] = false;
        
        $curdata = $this->CoRestDccProvisionerTarget->find('first', $args);
        
        if(!empty($curdata)) {
          $restdcc_url =  $curdata['CoRestDccProvisionerTarget']['restdcc_url'];
          $provider = new \League\OAuth2\Client\Provider\GenericProvider([
                'clientId'                => $curdata['CoRestDccProvisionerTarget']['client_id'],
                'clientSecret'            => $curdata['CoRestDccProvisionerTarget']['client_secret'],
                'redirectUri'             => $redirect_uri,
                'urlAuthorize'            => $restdcc_url . '/oauth/oauth2/auth',
                'urlAccessToken'          => $restdcc_url . '/oauth/oauth2/token',
                'urlResourceOwnerDetails' => $restdcc_url . '/oauth/userinfo'
          ]);
    
          $accessToken = $provider->getAccessToken('authorization_code', [
              'code' => $this->request->query['code']
          ]);
  
          $access_token = $accessToken->getToken();
          $refresh_token = $accessToken->getRefreshToken();

          if(!empty($access_token) && !empty($refresh_token)) {
            $this->CoRestDccProvisionerTarget->id = $id;
              
            if($this->CoRestDccProvisionerTarget->saveField('access_token', $access_token) &&
               $this->CoRestDccProvisionerTarget->saveField('refresh_token', $refresh_token) &&
               $this->CoRestDccProvisionerTarget->saveField('callback_url', $redirect_uri)) {
                  $this->performRedirect();
              } else {
                $this->Flash->set(_txt('er.db.save'), array('key' => 'error'));
              }
            } else {
              $this->Flash->set(_txt('er.restdccprovisioner.access_token'), array('key' => 'error'));
            }
        } else {
          $this->Flash->set(_txt('er.notfound', array(_txt('ct.co_github_provisioner_targets.1'), filter_var($id,FILTER_SANITIZE_SPECIAL_CHARS))),
                            array('key' => 'error'));
        }
      } else {
        $this->Flash->set(_txt('er.restdccprovisioner.state'), array('key' => 'error'));
      }
    }
    
    $this->performRedirect();
  }
  
  /**
   * Update a CO RestDccProvisioner Target.
   * - precondition: Model specific attributes in $this->request->data (optional)
   * - precondition: <id> must exist
   * - postcondition: On GET, $<object>s set
   * - postcondition: On POST success, object updated
   * - postcondition: On POST, session flash message updated
   *
   * @since  COmanage Registry v3.2.3
   * @param  integer CoRestDccProvisioner ID
   */
  
  public function edit($id) {
    parent::edit($id);
    
    // Set the callback URL
    $this->set('vv_restdcc_callback_url', array('plugin'     => 'rest_dcc_provisioner',
                                                'controller' => 'co_rest_dcc_provisioner_targets',
                                                'action'     => 'callback',
                                                $id));
    
    // Determine if the 'RestDcc' type has been configured
    
    $types = $this->CoRestDccProvisionerTarget->CoProvisioningTarget->Co->CoPerson->Identifier->types($this->cur_co['Co']['id'], 'type');
    
    // Pass a hint to the view regarding the DCC ID type
    $this->set('vv_restdcc_type', in_array('DccDocDbID', array_keys($types)));
    
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
    
    // Accept a callback from the OAuth2 server?
    $p['callback'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Delete an existing CO Provisioning Target?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Edit an existing CO Provisioning Target?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View all existing CO Provisioning Targets?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin']);
    
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
       && !empty($this->viewVars['co_rest_dcc_provisioner_targets'][0]['CoRestDccProvisionerTarget']['client_id'])) {
      // This is a save operation, so get a (new) access token
      
      $restdcc_url = $this->viewVars['co_rest_dcc_provisioner_targets'][0]['CoRestDccProvisionerTarget']['restdcc_url'];
      $redirect_uri = urlencode(Router::url(array('plugin'     => 'rest_dcc_provisioner',
                                                  'controller' => 'co_rest_dcc_provisioner_targets',
                                                  'action'     => 'callback',
                                                  $this->viewVars['co_rest_dcc_provisioner_targets'][0]['CoRestDccProvisionerTarget']['id']
                                                  ), true));
      $this->Session->write('Plugin.RestDccProvisioner.redirect_uri', $redirect_uri);

      $provider = new \League\OAuth2\Client\Provider\GenericProvider([
            'clientId'                => urlencode($this->viewVars['co_rest_dcc_provisioner_targets'][0]['CoRestDccProvisionerTarget']['client_id']),
            'clientSecret'            => urlencode($this->viewVars['co_rest_dcc_provisioner_targets'][0]['CoRestDccProvisionerTarget']['client_secret']),
            'redirectUri'             => $redirect_uri,
            'urlAuthorize'            => $restdcc_url . '/oauth/oauth2/auth',
            'urlAccessToken'          => $restdcc_url . '/oauth/oauth2/token',
            'urlResourceOwnerDetails' => $restdcc_url . '/oauth/userinfo'
      ]);

      // ask for a refresh token
      $options = [
          'scope' => 'offline'
      ];

      $session_auth_url = $provider->getAuthorizationUrl($options);

      // Put the state into the session so we can use it on callback
      $this->Session->write('Plugin.RestDccProvisioner.state', $provider->getState());

      $this->redirect($session_auth_url);
    }
    
    parent::performRedirect();
  }
  
}
