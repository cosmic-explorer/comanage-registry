<?php
/**
 * COmanage Registry CO Github Provisioner Target Model
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
 * @package       registry-plugin
 * @since         COmanage Registry v0.9.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

//App::uses('CakeSession', 'Model/Datasource');

App::uses("CoProvisionerPluginTarget", "Model");

// This file is generated by Composer
require_once APP . "AvailablePlugin" . DS . "GithubProvisioner" . DS . "Vendor" . DS . "autoload.php";

use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Cache\Adapter\Filesystem\FilesystemCachePool;

class CoGithubProvisionerTarget extends CoProvisionerPluginTarget {
  // Define class name for cake
  public $name = "CoGithubProvisionerTarget";
  
  // Add behaviors
  public $actsAs = array('Containable');
  
  // Association rules from this model to other models
  public $belongsTo = array("CoProvisioningTarget");
  
  // Default display field for cake generated views
  public $displayField = "github_user";
  
  // Validation rules for table elements
  public $validate = array(
    'co_provisioning_target_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'message' => 'A CO Provisioning Target ID must be provided'
    ),
    'github_user' => array(
      'rule' => 'notBlank'
    ),
    'github_org' => array(
      'rule' => array('maxlength', 80)
    ),
    'client_id' => array(
      'rule' => 'notBlank'
    ),
    'client_secret' => array(
      'rule' => 'notBlank'
    ),
    'access_token' => array(
      'rule' => array('maxlength', 80)
    ),
    'provision_group_members' => array(
      'rule' => 'boolean'
    ),
    'remove_unknown_members' => array(
      'rule' => 'boolean'
    ),
    'provision_ssh_keys' => array(
      'rule' => 'boolean'
    )
  );
  
  /**
   * Calculate the GitHub scope corresponding to the current provisioner settings.
   *
   * @since  COmanage Registry v0.9.1
   * @param  Array CO Provisioning Target data
   * @return String GitHub scope string
   */
  
  public function calculateScope($coProvisioningTargetData) {
    $scopes = array();
    
    if(isset($coProvisioningTargetData['CoGithubProvisionerTarget']['provision_group_members'])
       && $coProvisioningTargetData['CoGithubProvisionerTarget']['provision_group_members']) {
      // remove_unknown_members requires provision_group_members
      $scopes[] = 'admin:org';
    }
    
    /*
     * This only works per-user, not for an admin-y user the way group management works (CO-944)
     * 
    if(isset($coProvisioningTargetData['CoGithubProvisionerTarget']['provision_ssh_keys'])
       && $coProvisioningTargetData['CoGithubProvisionerTarget']['provision_ssh_keys']) {
      // Note "write" does not allow delete of existing key
      $scopes[] = 'write:public_key';
    }
     */
    
    return join(',', $scopes);
  }
  
  /**
   * Establish a connection to the GitHub API.
   *
   * @since  COmanage Registry v0.9.1
   * @param  String $token GitHub access token
   * @return Object Github Client
   * @throws Exception
   */
  
  protected function ghConnect($token) {
    $filesystemAdapter = new Local(APP . '/tmp/cache/github-api-cache');
    $filesystem = new Filesystem($filesystemAdapter);
    $pool = new FilesystemCachePool($filesystem);

    $client = new \Github\Client();
    $client->addCache($pool);

    $client->authenticate($token, null, Github\Client::AUTH_HTTP_TOKEN);

    return $client;
  }
  
  /**
   * Determine which Organizations can be managed (are owned by) the GitHub user
   * represented by the OAuth token.
   *
   * @since  COmanage Registry v0.9.1
   * @param  String $token GitHub access token
   * @param  String $username GitHub username
   * @return Array An array of GitHub Organizations (in key/value format, suitable for passing to a view)
   * @throws Exception
   */

  public function ownedOrganizations($token, $username) {
    // Github client operations will throw exceptions on error
    
    $client = $this->ghConnect($token);
    
    $orgs = $client->api('user')->orgs($username);
    
    $ownedOrgs = array();

    foreach($orgs as $org) {
            $ownedOrgs[ $org['login'] ] = $org['login'];
      }
    
    return $ownedOrgs;
  }
  
  /**
   * Provision for the specified CO Person.
   *
   * @since  COmanage Registry v0.9.1
   * @param  Array CO Provisioning Target data
   * @param  ProvisioningActionEnum Registry transaction type triggering provisioning
   * @param  Array Provisioning data, populated with ['CoPerson'] or ['CoGroup']
   * @return Boolean True on success
   * @throws RuntimeException
   */
  
  public function provision($coProvisioningTargetData, $op, $provisioningData) {
    // What operations are configured?
    $provisionGroup = (isset($coProvisioningTargetData['CoGithubProvisionerTarget']['provision_group_members'])
                       && $coProvisioningTargetData['CoGithubProvisionerTarget']['provision_group_members']);
    
    $provisionSsh = (isset($coProvisioningTargetData['CoGithubProvisionerTarget']['provision_ssh_keys'])
                     && $coProvisioningTargetData['CoGithubProvisionerTarget']['provision_ssh_keys']);
    
    $removeUnknown = (isset($coProvisioningTargetData['CoGithubProvisionerTarget']['remove_unknown_members'])
                      && $coProvisioningTargetData['CoGithubProvisionerTarget']['remove_unknown_members']);
    
    // For person ops, the github id to manage
    $githubid = null;
    
    // If this is a person related operation, we need a github identifier to proceed.
    
    if(!empty($provisioningData['CoPerson']['id'])) {
      if(!empty($provisioningData['Identifier'])) {
        foreach($provisioningData['Identifier'] as $i) {
          if($i['type'] == 'GitHub'
             && !empty($i['identifier'])
             && $i['status'] == StatusEnum::Active) {
            $githubid = trim($i['identifier']);
            break;
          }
        }
      }
      
      if(!$githubid) {
       // FIXME patch to work around greyed out dialog box
       return true;
        throw new RuntimeException(_txt('er.githubprovisioner.github_id') . ' (CoPerson ID ' . $provisioningData['CoPerson']['id'] . ')');
      }
    }
    
    // What actions should we run?
    $syncGroupsForPerson = false;
    $syncKeysForPerson = false;
    $syncMembersForGroup = false;
    
    switch($op) {
      case ProvisioningActionEnum::CoPersonAdded:
      case ProvisioningActionEnum::CoPersonPetitionProvisioned:
      case ProvisioningActionEnum::CoPersonPipelineProvisioned:
      case ProvisioningActionEnum::CoPersonReprovisionRequested:
      case ProvisioningActionEnum::CoPersonUnexpired:
      case ProvisioningActionEnum::CoPersonUpdated:
        $syncGroupsForPerson = true;
        $syncKeysForPerson = true;
        break;
      case ProvisioningActionEnum::CoPersonDeleted:
        // We don't treat CoPersonDeleted specially, because the group membership should have been
        // deleted before the person. Note there is a bit of a race condition, in that if
        // the github identifier is deleted before the group membership, we won't be able
        // to deprovision. However, the CoGroupMember relationship is defined first in
        // Model/CoPerson.php, so this should happen in the correct order.
        break;
      case ProvisioningActionEnum::CoPersonExpired:
        // Delete group memberships, but not ssh keys
        $syncGroupsForPerson = true;
        break;
      case ProvisioningActionEnum::CoPersonEnteredGracePeriod:
        // We don't do anything on grace period
        break;
      case ProvisioningActionEnum::CoGroupAdded:
      case ProvisioningActionEnum::CoGroupUpdated:
      case ProvisioningActionEnum::CoGroupReprovisionRequested:
        // We'll sync the memberships on these actions, even though for added and
        // updated it usually isn't necessary.
        $syncMembersForGroup = true;
        break;
      case ProvisioningActionEnum::CoGroupDeleted:
        // As for CoPersonDeleted, there shouldn't really be anything to do here
        // since the memberships should already have been deleted.
        break;
      default:
        throw new RuntimeException("Not Implemented");
        break;
    }
    
    // Execute the actions identified, if configured
    
    if($provisionGroup) {
      if($syncGroupsForPerson) {
        $this->syncGroupsForCoPerson($coProvisioningTargetData['CoGithubProvisionerTarget']['access_token'],
                                     $coProvisioningTargetData['CoGithubProvisionerTarget']['github_user'],
                                     $coProvisioningTargetData['CoGithubProvisionerTarget']['github_org'],
                                     $provisioningData['CoPerson']['id'],
                                     $provisioningData['Co']['id'],
                                     $githubid,
                                     $provisioningData['CoGroupMember']);
      }
      
      if($syncMembersForGroup) {
        $this->syncMembersForCoGroup($coProvisioningTargetData['CoGithubProvisionerTarget']['access_token'],
                                     $coProvisioningTargetData['CoGithubProvisionerTarget']['github_org'],
                                     $provisioningData['CoGroup']['name'],
                                     $provisioningData['CoGroup']['id'],
                                     $removeUnknown);
      }
    }
    
    if($provisionSsh) {
      if($syncKeysForPerson) {
        $this->syncSshKeysForCoPerson($coProvisioningTargetData['CoGithubProvisionerTarget']['access_token'],
                                      $coProvisioningTargetData['CoGithubProvisionerTarget']['github_user'],
                                      $provisioningData['CoPerson']['id'],
                                      $provisioningData['SshKey']);
      }
    }
    
    return true;
  }
  
  /**
   * Synchronize GitHub Team memberships for a CO Person.
   *
   * @since  COmanage Registry v0.9.1
   * @param  String  $token         GitHub access token
   * @param  String  $organization  GitHub organization
   * @param  String  $groupName     Group name
   * @param  String  $coId          CO ID
   * @param  Array   $groupMembers  Current set of group memberships for $username
   * @param  Boolean $removeUnknown If true, unknown GitHub Team Members are removed
   * @return Boolean true if groups are successfully synced
   * @throws Exception
   */
  
  protected function syncMembersForCoGroup($token, $organization, $groupName, $coGroupId, $removeUnknown=false) {
    // In order to operate on a Team, we need its ID, not its name. To get that,
    // we have to walk the list of Teams.

    $client = $this->ghConnect($token);
    
    $teams = $client->api('team')->all($organization);
    $teamid = null;
    
    foreach($teams as $t) {
      if($groupName == $t['name']) {
        // This is the team we want
        $teamid = $t['id'];
        break;
      }
    }
    
    if(!$teamid) {
      // FIXME patch to work around greyed out dialog box
      return true;
      throw new RuntimeException(_txt('er.githubprovisioner.team.notfound'));
    }
    
    // Now that we have the ID, pull the members of the team and convert to a hash
    
    $members = $client->api('team')->members($teamid);
    
    $membersHash = array();
    
    foreach($members as $m) {
      $membersHash[ $m['login'] ] = true;
    }

    // Walk through the CoGroupMembers and add any (with a GitHub identifier) to the group that
    // aren't currently in it. While we're here, assemble a list of known GitHub identifiers.
    
    $githubids = array();

    $args = array();
    $args['conditions']['CoGroupMember.co_group_id'] = $coGroupId;
    $args['conditions']['CoGroupMember.member'] = true;
    // Only pull currently valid group memberships
    $args['conditions']['AND'][] = array(
      'OR' => array(
        'CoGroupMember.valid_from IS NULL',
        'CoGroupMember.valid_from < ' => date('Y-m-d H:i:s', time())
      )
    );
    $args['conditions']['AND'][] = array(
      'OR' => array(
        'CoGroupMember.valid_through IS NULL',
        'CoGroupMember.valid_through > ' => date('Y-m-d H:i:s', time())
      )
    );
    $args['contain']['CoPerson'] = array(// We only need Identifiers for this provisioning target.
                                         // While Containable allows us to filter, Changelog doesn't
                                         // currently support that. So we pull all Identifiers and
                                         // filter later with Hash.
                                         'Identifier');

    $groupMembers = $this->CoProvisioningTarget
                         ->Co
                         ->CoGroup
                         ->CoGroupMember
                         ->find('all', $args);

    foreach($groupMembers as $gm) {
      if(isset($gm['CoGroupMember']['member']) && $gm['CoGroupMember']['member']) {
        $gituser = null;
        
        if(!empty($gm['CoPerson']['Identifier'])) {
          foreach($gm['CoPerson']['Identifier'] as $i) {
            if($i['type'] == 'GitHub') {
              // Match found
              $gituser = trim($i['identifier']);
              break;
            }
          }
        }
        
        if($gituser && ($gm['CoPerson']['status']==StatusEnum::Active)) {
          $githubids[ $gituser ] = true;
          $this->syncGroupMember($client, $gituser, $groupName, $teamid, $gm['CoPerson']['id'], $gituser, true);
        }
      }
    }
    
    if($removeUnknown) {
      // Walk through the members list and remove any not in the COmanage group.
      // We handle this directly since there isn't necessarily a CO Person attached
      // to the member we are removing.
      //
      // Note that if the user to be removed has not yet accepted their invitation,
      // the pending invitation will not be removed and the user could join the group.
      //
      // We can't fix this, as KnpLabs/php-github-api does not currently implement
      // invitations: https://github.com/KnpLabs/php-github-api/issues/713
      //
      // See https://todos.internet2.edu/browse/CO-1818

      foreach($members as $m) {
        if(!isset($githubids[ $m['login'] ])) {
          $client->api('team')->removeMember($teamid, $m['login']);
        }
      }
    }
    
    return true;
  }
  
  /**
   * Synchronize a single GitHub Team membership for a CO Person.
   *
   * @since  COmanage Registry v0.9.1
   * @param  Object  $client       GitHub API client connection
   * @param  String  $username     GitHub username
   * @param  String  $groupName    GitHub/COmanage team name
   * @param  String  $teamId       GitHub team ID
   * @param  String  $coPersonId   CO Person ID
   * @param  Boolean $isMember     True if subject is a member of the COmanage group
   * @return Boolean true if group is successfully synced
   * @throws Exception
   */
  
  protected function syncGroupMember($client, $username, $groupName, $teamId, $coPersonId, $githubid, $isMember) {
    // Is this person currently a member?
    $inTeam = false;
    
    try {
      $response = $client->api('team')->check($teamId, $githubid);
      $inTeam = true;
    }
    catch(Exception $e) {
      // API returns 404 if not a member
    }
    
    if($isMember) {
      // Currently in COmanage group
      
      if(!$inTeam) {
        // Add to GH team -- Note that the user being added must already be a member
        // of at least one other team on the same organization.
        // https://developer.github.com/v3/orgs/teams/#get-team-member
        // XXX We really want to add membership (not member), which is currently in
        // preview and not currently supported by the php API library. (CO-942)
        // https://developer.github.com/v3/orgs/teams/#add-team-membership
        
        $client->api('team')->addMember($teamId, $githubid);
        // Rather than catch an exception here (or in history record),
        // we'll just abort and let it percolate up the stack
        
        $this->CoProvisioningTarget->Co->CoPerson->HistoryRecord->record($coPersonId,
                                                                         null,
                                                                         null,
                                                                         // There should be a better way to get the actor but
                                                                         // at the moment there isn't
                                                                         CakeSession::read('Auth.User.co_person_id'),
                                                                         ActionEnum::ProvisionerAction,
                                                                         _txt('pl.githubprovisioner.added', array($groupName)));
      }
    } else {
      // Not currently in COmanage group
      
      if($inTeam) {
        // Remove from GH team
        
        $client->api('team')->removeMember($teamId, $githubid);
        
        $this->CoProvisioningTarget->Co->CoPerson->HistoryRecord->record($coPersonId,
                                                                         null,
                                                                         null,
                                                                         // There should be a better way to get the actor but
                                                                         // at the moment there isn't
                                                                         CakeSession::read('Auth.User.co_person_id'),
                                                                         ActionEnum::ProvisionerAction,
                                                                         _txt('pl.githubprovisioner.removed', array($groupName)));          }
    }
    
    return true;
  }
  
  /**
   * Synchronize GitHub Team memberships for a CO Person.
   *
   * @since  COmanage Registry v0.9.1
   * @param  String $token        GitHub access token
   * @param  String $username     GitHub username
   * @param  String $organization GitHub organization
   * @param  String $coPersonId   CO Person ID
   * @param  String $coId         CO ID
   * @param  Array  $groupMembers Current set of group memberships for $username
   * @return Boolean true if groups are successfully synced
   * @throws Exception
   */
  
  protected function syncGroupsForCoPerson($token, $username, $organization, $coPersonId, $coId, $githubid, $groupMembers) {
    // Pull GH teams
    
    $client = $this->ghConnect($token);
    
    $teams = $client->api('team')->all($organization);
    
    // Create an index of teams by name for easier referencing
    
    $teamsHash = array();
    
    foreach($teams as $t) {
      $teamsHash[ $t['name'] ] = $t;
    }

    // Pull current COmanage groups
    
    $args = array();
    $args['conditions']['CoGroup.co_id'] = $coId;
    $args['conditions']['CoGroup.status'] = StatusEnum::Active;
    $args['contain'] = false;
    
    $groups = $this->CoProvisioningTarget->Co->CoGroup->find('all', $args);
    
    // Push current group memberships into an array to make it easier to work with
    
    $curGroups = array();
    
    foreach($groupMembers as $gm) {
      if(isset($gm['member']) && $gm['member']) {
        $curGroups[ $gm['CoGroup']['name'] ] = true;
      }
    }
    
    // Walk through the groups
    
    foreach($groups as $g) {
      $gname = $g['CoGroup']['name'];
      
      // Is there a corresponding team?
      
      if(isset($teamsHash[ $gname ])) {
        $this->syncGroupMember($client, $username, $gname, $teamsHash[ $gname ]['id'], $coPersonId, $githubid, isset($curGroups[ $gname ]));
      }
    }
    
    return true;
  }

  /**
   * Synchronize SSH Keys for a CO Person. Note keys are only written to GitHub, they are not deleted.
   *
   * @since  COmanage Registry v0.9.1
   * @param  String $token        GitHub access token
   * @param  String $username     GitHub username
   * @param  String $coPersonId   CO Person ID
   * @param  Array  $sshKeys      Current set of ssh keys for $username
   * @return Boolean true if keys are successfully synced
   * @throws Exception
   */
  
  protected function syncSshKeysForCoPerson($token, $username, $coPersonId, $sshKeys) {
    /* XXX CO-944
     * Actually, it turns out the GitHub API doesn't support writing an SSH Key for a user
     * without authenticating as them. Perhaps link this to the work needed to authenticate
     * their GitHub ID?
     *
     * This would require a less-changing callback URL and perhaps storing an access token per user
     */
    
    // Pull keys for GH user
    
    $client = $this->ghConnect($token);
    
    $curKeys = $client->api('user')->keys($username);
    
    // There typically won't be more than a couple of keys per user, so we don't bother
    // with optimizing search via hashes.
    
    global $ssh_ti;
    
    foreach($sshKeys as $k) {
      // Convert the key into the format returned by GH and see if it's there.
      // Note we only look at the key type and the key itself, we ignore the key title
      // (as known to GH) or comment (as known to COmanage).
      
      $fkey = $ssh_ti[ $k['type'] ] . " " . $k['skey'];
      
      $found = false;
      
      foreach($curKeys as $ck) {
        if($fkey == $ck['key']) {
          $found = true;
          break;
        }
      }
      
      if($found) {
        // Push the key into GitHub
        // XXX This isn't supported for an admin user. Need to authenticate with an
        // OAuth token for the target user. Not currently implemented.
        
        // debug("Pushing key " . $fkey);
      }
    }
    
    return true;
  }
}
