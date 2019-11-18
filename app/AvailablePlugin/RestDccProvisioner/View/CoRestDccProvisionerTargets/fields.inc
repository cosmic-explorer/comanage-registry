<!--
/**
 * COmanage Registry CO RestDCCDocDB Provisioning Target Fields
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
 * @since         COmanage Registry v3.2.2
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
-->
<?php
  // Determine if fields are editable
  $e = false;
  
  if(($this->action == "add" && $permissions['add']) || ($this->action == "edit" && $permissions['edit']))
    $e = true;
    
  // We shouldn't get here if we don't have at least read permission, but check just in case
  
  if(!$e && !$permissions['view'])
    return false;
  
  print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";
  print $this->Form->hidden('co_provisioning_target_id', array('default' => $vv_ptid)) . "\n";
?>
<?php if(empty($co_rest_dcc_provisioner_targets[0]['CoRestDccProvisionerTarget']['client_id'])): ?>
  <div class="co-info-topbox">
    <i class="material-icons">info</i>
    <?php print _txt('pl.restdccprovisioner.register',
      array($this->Html->url($vv_restdcc_callback_url, true))); ?>
  </div>
<?php elseif(empty($co_rest_dcc_provisioner_targets[0]['CoRestDccProvisionerTarget']['access_token'])): ?>
  <div class="co-info-topbox">
    <i class="material-icons">info</i>
    <?php print _txt('pl.restdccprovisioner.token.none'); ?>
  </div>
<?php endif; // client_id/access_token ?>
<?php if(!$vv_restdcc_type): ?>
  <div class="co-info-topbox">
    <i class="material-icons">info</i>
    <?php print _txt('pl.restdccprovisioner.type', array($this->Html->url($vv_extended_type_url))); ?>
  </div>
<?php endif; // github_type ?>

<ul id="<?php print $this->action; ?>_co_rest_dcc_provisioner_target" class="fields form-list form-list-admin">
    <li>
      <div class="field-name">
        <div class="field-title">
          <?php print _txt('pl.restdccprovisioner.callback_url'); ?>
        </div>  
      </div>
      <div class="field-info">
        <input type="text" readonly="true" size="60" value="<?php print $this->Html->url($vv_restdcc_callback_url, true); ?>" />
      </div>
    </li>
    <li>
      <div class="field-name">
        <div class="field-title">
          <?php print _txt('pl.restdccprovisioner.restdcc_url'); ?>
          <span class="required">*</span>
        </div>
        <div class="field-desc"><?php print _txt('pl.restdccprovisioner.restdcc_url.desc'); ?></div>
      </div>
      <div class="field-info">
        <?php print ($e ? $this->Form->input('restdcc_url') : filter_var($co_rest_dcc_provisioner_targets[0]['CoRestDccProvisionerTarget']['restdcc_url'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </div>
    </li>
    <li>
      <div class="field-name">
        <div class="field-title">
          <?php print _txt('pl.restdccprovisioner.client_id'); ?>
          <span class="required">*</span>
        </div>
        <div class="field-desc"><?php print _txt('pl.restdccprovisioner.client_id.desc'); ?></div>
      </div>
      <div class="field-info">
        <?php print ($e
                     ? $this->Form->input('client_id', array('type' => 'string'))
          : filter_var($co_rest_dcc_provisioner_targets[0]['CoRestDccProvisionerTarget']['client_id'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </div>
    </li>
    <li>
      <div class="field-name">
        <div class="field-title">
          <?php print _txt('pl.restdccprovisioner.client_secret'); ?>
          <span class="required">*</span>
        </div>
        <div class="field-desc"><?php print _txt('pl.restdccprovisioner.client_secret.desc'); ?></div>
      </div>
      <div class="field-info">
        <?php print ($e
                     ? $this->Form->input('client_secret')
                     : (!empty($co_rest_dcc_provisioner_targets[0]['CoRestDccProvisionerTarget']['client_secret'])
                        ? "*"
                        : "")); ?>
      </div>
    </li>
  <?php if($e): ?>
    <li>
      <div class="field-name">
        <span class="required"><?php print _txt('fd.req'); ?></span>
      </div>
      <div class="field-info">
        <?php print $this->Form->submit($submit_label); ?>
        <div class="field-desc">
          <span class="ui-icon ui-icon-info"></span>
          <em><?php print _txt('pl.restdccprovisioner.oauth'); ?></em>
        </div>
      </div>
    </li>
  <?php endif; ?>
</ul>
