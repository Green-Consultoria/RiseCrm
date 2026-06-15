<?php $active_settings_tab = $active_settings_tab ?? "statuses"; ?>
<div id="page-content" class="page-wrapper clearfix green-mobile-ready green-crm-page">
    <div class="card">
        <div class="green-inner-card">
            <div class="page-title clearfix green-crm-page-header">
                <h1>Configuracoes Green</h1>
                <div class="title-button-group green-crm-title-actions"><?php echo modal_anchor(get_uri("green_crm/status_modal_form"), "<i data-feather='plus-circle' class='icon-16'></i> Novo status", ["class" => "btn btn-primary", "title" => "Novo status"]); ?></div>
            </div>

            <ul class="nav nav-tabs title green-settings-tabs" role="tablist">
                <li class="nav-item"><a class="nav-link <?php echo $active_settings_tab === "statuses" ? "active" : ""; ?>" href="<?php echo get_uri("green_crm/settings/statuses"); ?>"><i data-feather="flag" class="icon-16"></i> Status do funil</a></li>
                <li class="nav-item"><a class="nav-link <?php echo $active_settings_tab === "operators" ? "active" : ""; ?>" href="<?php echo get_uri("green_crm/settings/operators"); ?>"><i data-feather="briefcase" class="icon-16"></i> Operadoras</a></li>
                <li class="nav-item"><a class="nav-link <?php echo $active_settings_tab === "plans" ? "active" : ""; ?>" href="<?php echo get_uri("green_crm/settings/plans"); ?>"><i data-feather="layers" class="icon-16"></i> Planos</a></li>
            </ul>

            <div class="p20 green-crm-content-panel">
                <div class="text-off mb10">Status do funil de vendas. Configure a ordem e marque quais sao finais, ganhos ou perdidos.</div>
                <div class="table-responsive green-table-wrap"><table id="green-statuses-table" class="display green-crm-table green-table-settings" cellspacing="0" width="100%"></table></div>
            </div>
        </div>
    </div>
</div>
<script>
$(document).ready(function(){ $("#green-statuses-table").appTable({source:"<?php echo_uri("green_crm/statuses_list_data"); ?>", tableRefreshButton:true, columns:[{title:"Ordem"},{title:"Status"},{title:"Final"},{title:"Ganho"},{title:"Perdido"},{title:"<i data-feather='menu' class='icon-16'></i>", "class":"all text-center option w120"}], onInitComplete:function(){ if(window.feather){ feather.replace(); } }, onRelaodCallback:function(){ if(window.feather){ feather.replace(); } }}); if(window.feather){ feather.replace(); } });
</script>
