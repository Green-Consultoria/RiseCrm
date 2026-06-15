<div class="modal-body clearfix">
    <div class="container-fluid">
        <h4>Relatorio de importacao</h4>
        <p><strong>Batch:</strong> <?php echo (int) ($batch_id ?? 0); ?></p>
        <p><strong>Sucessos:</strong> <?php echo (int) ($success ?? 0); ?></p>
        <p><strong>Erros:</strong> <?php echo (int) ($errors ?? 0); ?></p>
        <?php if (!empty($rows)): ?>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead><tr><th>Linha</th><th>Acao</th><th>Entidade</th><th>ID</th><th>Erro</th><th>Aviso</th></tr></thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr><td><?php echo (int) $row->row_number; ?></td><td><?php echo esc($row->action); ?></td><td><?php echo esc($row->entity_type); ?></td><td><?php echo (int) $row->target_id; ?></td><td><?php echo esc($row->error_message); ?></td><td><?php echo esc($row->warning_message ?? ""); ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-default" data-bs-dismiss="modal" title="Fechar"><span data-feather="x-circle" class="icon-16"></span> Fechar</button></div>
