<div class="modal-body clearfix">
    <div class="container-fluid">
        <h5 class="mb15">Lead capturado &mdash; Facebook Lead Ads</h5>
        <table class="table table-bordered">
            <tr><th width="32%">Nome</th><td><?php echo esc($lead->full_name ?: "-"); ?></td></tr>
            <tr><th>Telefone</th><td><?php echo esc($lead->phone_original ?: ($lead->phone_normalized ?: "-")); ?></td></tr>
            <tr><th>Email</th><td><?php echo esc($lead->email ?: "-"); ?></td></tr>
            <tr><th>Região</th><td><?php echo esc($lead->region ?: "-"); ?></td></tr>
            <tr><th>Campanha</th><td><?php echo esc($lead->campaign_name ?: "-"); ?></td></tr>
            <tr><th>Anúncio</th><td><?php echo esc($lead->ad_name ?: "-"); ?></td></tr>
            <tr><th>Formulário</th><td><?php echo esc($lead->form_name ?: ($lead->facebook_form_id ?: "-")); ?></td></tr>
            <tr><th>Facebook Lead ID</th><td><?php echo esc($lead->facebook_lead_id ?: "-"); ?></td></tr>
            <tr><th>Capturado em</th><td><?php echo $lead->facebook_created_time ? format_to_datetime($lead->facebook_created_time) : "-"; ?></td></tr>
            <tr>
                <th>Status do processamento</th>
                <td>
                    <?php echo esc($lead->process_status); ?>
                    <?php echo $lead->process_message ? "&mdash; " . esc($lead->process_message) : ""; ?>
                </td>
            </tr>
            <tr>
                <th>Lead no Green CRM</th>
                <td>
                    <?php if (!empty($lead->green_lead_id)): ?>
                        <?php echo anchor(get_uri("green_crm/lead/" . (int) $lead->green_lead_id), "Abrir Lead #" . (int) $lead->green_lead_id, ["target" => "_blank", "rel" => "noopener"]); ?>
                        <?php if ($green_lead && !empty($green_lead->lead_code)): ?>
                            <span class="text-off">(<?php echo esc($green_lead->lead_code); ?>)</span>
                        <?php endif; ?>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
            </tr>
        </table>

        <?php $extra = $lead->extra_fields ? json_decode($lead->extra_fields, true) : []; ?>
        <?php if (is_array($extra) && count($extra)): ?>
            <h5 class="mb10">Outros campos do formulário</h5>
            <table class="table table-bordered">
                <?php foreach ($extra as $key => $value): ?>
                    <tr>
                        <th width="32%"><?php echo esc($key); ?></th>
                        <td><?php echo esc(is_array($value) ? implode(", ", $value) : $value); ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal">
        <span data-feather="x" class="icon-16"></span> Fechar
    </button>
</div>

<script type="text/javascript">
    if (window.feather) {
        feather.replace();
    }
</script>
