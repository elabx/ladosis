<?php
if ($user->hasRole("superuser")) {
$currentPage = $this->pages->findOne("template='simple_contact_form_messages'"); ?>
<h1><?= $currentPage->title; ?></h1>
<table>
<thead>
<tr>
<th>FullName</th>
<th>Email</th>
<th>Message</th>
<th>Date</th>
<th>Ip</th>
</tr>
</thead>
<tbody>
<?php foreach ($currentPage->repeater_scfmessages->sort('-scf-date') as $message) { ?>
<tr>
<td><?= $message->scf_fullName; ?></td>
<td><a href='mailto:<?= $message->scf_email; ?>'><?= $message->scf_email; ?></a></td>
<td><?= $message->scf_message; ?></td>
<td><?= $message->scf_date; ?></td>
<td><?= $message->scf_ip; ?></td>
</tr>
<?php } ?>
</tbody>
</table>
<?php } else { 
$session->redirect($pages->get("/")->url);
}?>