<div id="page-content" class="page-wrapper clearfix green-mobile-ready green-crm-page">
    <div class="card">
        <div class="page-title clearfix green-crm-page-header">
            <h1>Cotações</h1>
            <div class="title-button-group green-crm-title-actions">
                <?php echo modal_anchor(get_uri("green_crm/quote_modal_form"), "<i data-feather='plus-circle' class='icon-16'></i> Nova cotação", ["class" => "btn btn-primary", "title" => "Nova cotação"]); ?>
            </div>
        </div>

        <div class="p20 green-crm-content-panel">
            <div class="text-off mb10">Comparador comercial de planos e propostas enviadas aos leads.</div>
            <div class="table-responsive green-table-wrap">
                <table id="green-quotes-table" class="display green-crm-table green-table-quotes" cellspacing="0" width="100%"></table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function(){
    $("#green-quotes-table").appTable({
        source: "<?php echo_uri("green_crm/quotes_list_data"); ?>",
        tableRefreshButton: true,
        columns: [
            {title:"Código", "class":"all green-col-code"},
            {title:"Cliente", "class":"all green-col-client"},
            {title:"Lead", "class":"green-col-lead"},
            {title:"Status", "class":"green-col-status"},
            {title:"Válida até", "class":"green-col-date"},
            {title:"<i data-feather='menu' class='icon-16'></i>", "class":"all text-center option w180"}
        ],
        printColumns: [0,1,2,3,4],
        xlsColumns: [0,1,2,3,4],
        onInitComplete: function () {
            if (window.feather) {
                feather.replace();
            }
        },
        onRelaodCallback: function () {
            if (window.feather) {
                feather.replace();
            }
        }
    });

    $("body").on("click", ".green-send-quote", function(){
        appAjaxRequest({url:"<?php echo_uri("green_crm/send_quote"); ?>", type:"POST", dataType:"json", data:{id:$(this).data("id")}, success:function(r){r.success?appAlert.success(r.message):appAlert.error(r.message); $("#green-quotes-table").appTable({reload:true});}});
    });

    $("body").on("click", ".green-accept-quote", function(){
        appAjaxRequest({url:"<?php echo_uri("green_crm/accept_quote"); ?>", type:"POST", dataType:"json", data:{id:$(this).data("id")}, success:function(r){r.success?appAlert.success(r.message):appAlert.error(r.message); $("#green-quotes-table").appTable({reload:true});}});
    });

    $("body").on("click", ".green-convert-selected-quote-list", function(){
        appAjaxRequest({
            url:"<?php echo_uri("green_crm/convert_selected_quote_option_to_sale"); ?>",
            type:"POST",
            dataType:"json",
            data:{id:$(this).data("id")},
            success:function(r){
                r.success ? appAlert.success(r.message) : appAlert.error(r.message);
                if (r.success) {
                    window.location.href = "<?php echo get_uri("green_crm/sales"); ?>";
                }
            }
        });
    });

    if (window.feather) {
        feather.replace();
    }
});
</script>
