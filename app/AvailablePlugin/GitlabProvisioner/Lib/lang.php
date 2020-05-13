<?php
/**
 * COmanage Registry Gitlab Provisioner Plugin Language File
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
  
global $cm_lang, $cm_texts;

// When localizing, the number in format specifications (eg: %1$s) indicates the argument
// position as passed to _txt.  This can be used to process the arguments in
// a different order than they were passed.

$cm_gitlab_provisioner_texts['en_US'] = array(
  // Titles, per-controller
  'ct.co_gitlab_provisioner_targets.1'  => 'Gitlab Provisioner Target',
  'ct.co_gitlab_provisioner_targets.pl' => 'Gitlab Provisioner Targets',
  
  // Error messages
  'er.gitlabprovisioner.access_token'   => 'Access token not received',
  'er.gitlabprovisioner.gitlab_id'      => 'No GitLab identifier found',
  'er.gitlabprovisioner.orgs.none'      => 'There are no owned Organizations available to be managed',
  'er.gitlabprovisioner.team.notfound'  => 'No corresponding GitLab team found for this group',
  'er.gitlabprovisioner.state'          => 'State token mismatch',
  
  // Plugin texts
  'pl.gitlabprovisioner.added'          => 'Added to GitLab Team "%1$s"',
  'pl.gitlabprovisioner.callback_url'   => 'GitLab Callback URL',
  'pl.gitlabprovisioner.client_id'      => 'GitLab Client ID',
  'pl.gitlabprovisioner.client_id.desc' => 'The Client ID provided by GitLab after registering this application.',
  'pl.gitlabprovisioner.client_secret'  => 'GitLab Client Secret',
  'pl.gitlabprovisioner.client_secret.desc' => 'The Client Secret provided by GitLab after registering this application.',
  'pl.gitlabprovisioner.gitlab_org'    => 'GitLab Organization',
  'pl.gitlabprovisioner.gitlab_org.desc' => 'The GitLab Organization to be managed by this provisioner.',
  'pl.gitlabprovisioner.gitlab_user'    => 'GitLab Username',
  'pl.gitlabprovisioner.gitlab_user.desc' => 'The GitLab Username to be used by this provisioner. The GitLab user must have sufficient privileges for the operations enabled.',
  'pl.gitlabprovisioner.oauth'          => 'After clicking <i>Save</i>, you may be asked by GitLab to authenticate and/or authorize COmanage in order to continue. You should also review <a href="https://gitlab.com/site/terms">GitLab\'s Terms and Conditions</a> before proceeding.',
  'pl.gitlabprovisioner.org.select'     => 'Please select an Organization to manage.',
  'pl.gitlabprovisioner.provision_group_members' => 'Provision Group Memberships to GitLab',
  'pl.gitlabprovisioner.provision_group_members.desc' => 'If enabled, active COmanage users with a "GitLab" Identifier will be provisioned into GitLab Teams whose names match COmanage groups.',
  'pl.gitlabprovisioner.provision_ssh_keys' => 'Provision SSH Keys to GitLab',
  'pl.gitlabprovisioner.provision_ssh_keys.desc' => 'If enabled, COmanage users with a "GitLab" Identifier and with associated SSH Keys will have their keys provisioned to their GitLab accounts.',
  'pl.gitlabprovisioner.register'       => 'First, <a href="https://gitlab.com/settings/applications/new">register COmanage as an application with GitLab</a>.<br />
                                            Set the <i>Authorization callback URL</i> to be <pre>%1$s</pre><br />
                                            After registering, copy the Client ID and Client Secret values assigned by GitLab here.',
  'pl.gitlabprovisioner.remove_unknown_members' => 'Remove Unknown Members from GitLab Teams',
  'pl.gitlabprovisioner.remove_unknown_members.desc' => 'If enabled, members of GitLab teams who do not correspond to a COmanage user with a "GitLab" Identifier will be removed.',
  'pl.gitlabprovisioner.removed'        => 'Removed from GitLab Team "%1$s"',
  'pl.gitlabprovisioner.token.none'     => 'No access token has been received, and so provisioning cannot be completed. To obtain a token, please click "Save".',
  'pl.gitlabprovisioner.token.ok'       => 'Access token verified and configuration updated',
  'pl.gitlabprovisioner.type'           => 'The "GitLab" (case sensitive) Extended Type should be created before using this provisioner. <a href="%1$s">Click here</a> to add it.'
);
