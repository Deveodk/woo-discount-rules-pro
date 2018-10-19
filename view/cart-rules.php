<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

$active = 'cart-rules';
include_once(WOO_DISCOUNT_DIR . '/view/includes/header.php');
include_once(WOO_DISCOUNT_DIR . '/view/includes/menu.php');

$config = (isset($config)) ? $config : '{}';

$data = array();
$rule_list = $config;
global $woocommerce;

$flycartWooDiscountRulesPurchase = new FlycartWooDiscountRulesPurchase();
$isPro = $flycartWooDiscountRulesPurchase->isPro();

$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
$current_url = remove_query_arg( 'paged', $current_url );
if ( isset( $_GET['order'] ) && 'asc' === $_GET['order'] ) {
    $current_order = 'asc';
} else {
    $current_order = 'desc';
}
if ( isset( $_GET['orderby'] ) ) {
    $current_orderby = $_GET['orderby'];
} else {
    $current_orderby = '';
}
$orderby = 'ordering';
$desc_first = 0 ;
if ( $current_orderby === $orderby ) {
    $order = 'desc' === $current_order ? 'asc' : 'desc';
    $class[] = 'sorted';
    $class[] = $current_order;
} else {
    $order = $desc_first ? 'desc' : 'asc';
    $class[] = 'sortable';
    $class[] = $desc_first ? 'asc' : 'desc';
}
?>
    <div class="container-fluid woo_discount_loader_outer" id="cart_rule">
        <div class="row-fluid">
            <div class="<?php echo $isPro? 'col-md-12': 'col-md-8'; ?>">
                <div class="row">
                    <div class="row">
                        <div class="col-md-8">
                            <h4><?php esc_html_e('Cart Rules', 'woo-discount-rules'); ?></h4>
                        </div>
                        <div class="col-md-4 text-right">
                            <br/>
                            <a href="https://www.flycart.org/woocommerce-discount-rules-examples#cartdiscountexample" target="_blank" class="btn btn-info"><?php esc_html_e('View Examples', 'woo-discount-rules'); ?></a>
                            <a href="http://docs.flycart.org/woocommerce-discount-rules/cart-discount-rules" target="_blank" class="btn btn-info"><?php esc_html_e('Documentation', 'woo-discount-rules'); ?></a>
                        </div>
                        <hr>
                    </div>
                    <form id="woo_discount_list_form" method="post" action="?page=woo_discount_rules">
                        <div class="row">
                            <div class="col-md-4" id="add_new_rule_div">
                                <?php if (isset($rule_list)) {
                                    if (count($rule_list) >= 3 && !$pro) { ?>
                                        <a href="javascript:void(0)" class="btn btn-primary">
                                            <?php esc_html_e('You Reach Max. Rule Limit', 'woo-discount-rules'); ?>
                                        </a>
                                    <?php } else {
                                        ?>
                                        <a href="?page=woo_discount_rules&tab=cart-rules&type=new" id="add_new_rule" class="btn btn-primary">
                                            <?php esc_html_e('Add New Rule', 'woo-discount-rules'); ?>
                                        </a>
                                        <?php
                                    }
                                }

                                ?>

                            </div>
                            <div class="col-md-12">
                                <div class="woo_discount_rules_bulk_action_con">
                                    <div class="alignleft actions bulkactions">
                                        <select name="bulk_action" id="bulk-action-selector-top">
                                            <option value=""><?php esc_html_e('Bulk Actions', 'woo-discount-rules'); ?></option>
                                            <option value="publish"><?php esc_html_e('Enable rules', 'woo-discount-rules'); ?></option>
                                            <option value="unpublish"><?php esc_html_e('Disable rules', 'woo-discount-rules'); ?></option>
                                            <option value="delete"><?php esc_html_e('Delete rules', 'woo-discount-rules'); ?></option>
                                        </select>
                                        <input id="wdr_do_bulk_action" class="button action" value="<?php esc_html_e('Apply', 'woo-discount-rules'); ?>" type="button">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <br>
                        <div class="">
                            <div class="">
                                <table class="wp-list-table widefat fixed striped posts">
                                    <thead>
                                    <tr>
                                        <td id="cb" class="manage-column column-cb check-column">
                                            <input id="cb-select-all-1" type="checkbox" />
                                        </td>
                                        <th><?php esc_html_e('Name', 'woo-discount-rules'); ?></th>
                                        <th><?php esc_html_e('Start Date', 'woo-discount-rules'); ?></th>
                                        <th><?php esc_html_e('Expired On', 'woo-discount-rules'); ?></th>
                                        <th class="manage-column column-title column-primary sorted <?php echo $current_order; ?>" scope="col">
                                            <?php
                                            $column_display_name = esc_html__('Order', 'woo-discount-rules');
                                            $column_display_name = '<a href="' . esc_url( add_query_arg( compact( 'orderby', 'order' ), $current_url ) ) . '"><span>' . $column_display_name . '</span><span class="sorting-indicator"></span></a>';
                                            echo $column_display_name;
                                            ?>
                                        </th>
                                        <th><?php esc_html_e('Action', 'woo-discount-rules'); ?></th>
                                    </tr>
                                    </thead>
                                    <tbody id="cart_rule">
                                    <?php
                                    $i = 1;
                                    if (is_array($rule_list)) {
                                        if (count($rule_list) > 0) {
                                            foreach ($rule_list as $index => $rule) {
                                                if (!$pro && $i > 3) continue;
                                                $meta = $rule->meta;
                                                $status = isset($meta['status'][0]) ? $meta['status'][0] : 'disable';
                                                $class = 'btn btn-success';

                                                if ($status == 'publish') {
                                                    $class = 'btn btn-success';
                                                    $value = esc_html__('Disable', 'woo-discount-rules');
                                                } else {
                                                    $class = 'btn btn-warning';
                                                    $value = esc_html__('Enable', 'woo-discount-rules');
                                                }
                                                ?>

                                                <tr>
                                                    <th class="check-column">
                                                        <input id="cb-select-<?php echo $i; ?>" name="post[]" value="<?php echo $rule->ID; ?>" type="checkbox"/>
                                                    </th>
                                                    <td><?php echo(isset($meta['rule_name'][0]) ? $meta['rule_name'][0] : '-') ?></td>
                                                    <td><?php echo(isset($rule->date_from) ? $rule->date_from : '-') ?></td>
                                                    <td><?php echo(isset($rule->date_to) ? $rule->date_to : '-') ?></td>
                                                    <td><?php echo((isset($rule->rule_order) && ($rule->rule_order != '')) ? $rule->rule_order : ' - ') ?></td>
                                                    <td>
                                                        <a class="btn btn-primary" href="?page=woo_discount_rules&tab=cart-rules&view=<?php echo $rule->ID ?>">
                                                            <?php esc_html_e('Edit', 'woo-discount-rules'); ?>
                                                        </a>
                                                        <?php if($pro){ ?>
                                                            <button class="btn btn-primary duplicate_cart_rule_btn" data-id="<?php echo $rule->ID; ?>" type="button">
                                                                <?php esc_html_e('Duplicate', 'woo-discount-rules'); ?>
                                                            </button>
                                                        <?php } ?>
                                                        <a class="<?php echo $class; ?> cart_manage_status" id="state_<?php echo $rule->ID ?>">
                                                            <?php echo $value; ?>
                                                        </a>
                                                        <a class="btn btn-danger cart_delete_rule" id="delete_<?php echo $rule->ID ?>">
                                                            <?php esc_html_e('Delete', 'woo-discount-rules'); ?>
                                                        </a>
                                                    </td>
                                                </tr>
                                                <?php
                                                $i++;
                                            }
                                        }
                                    }
                                    ?>
                                    </tbody>
                                    <tfoot>
                                    <tr>
                                        <td id="cb" class="manage-column column-cb check-column">
                                            <input id="cb-select-all-1" type="checkbox" />
                                        </td>
                                        <th><?php esc_html_e('Name', 'woo-discount-rules'); ?></th>
                                        <th><?php esc_html_e('Start Date', 'woo-discount-rules'); ?></th>
                                        <th><?php esc_html_e('Expired On', 'woo-discount-rules'); ?></th>
                                        <th class="manage-column column-title column-primary sorted <?php echo $current_order; ?>" scope="col">
                                            <?php
                                            $column_display_name = esc_html__('Order', 'woo-discount-rules');
                                            $column_display_name = '<a href="' . esc_url( add_query_arg( compact( 'orderby', 'order' ), $current_url ) ) . '"><span>' . $column_display_name . '</span><span class="sorting-indicator"></span></a>';
                                            echo $column_display_name;
                                            ?>
                                        </th>
                                        <th><?php esc_html_e('Action', 'woo-discount-rules'); ?></th>
                                    </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                        <hr>

                        <input type="hidden" name="form" value="cart_rules">
                        <input type="hidden" id="ajax_path" value="<?php echo admin_url('admin-ajax.php') ?>">
                    </form>
                </div>
            </div>
            <?php if(!$isPro){ ?>
                <div class="col-md-1"></div>
                <!-- Sidebar -->
                <?php include_once(__DIR__ . '/template/sidebar.php'); ?>
                <!-- Sidebar END -->
            <?php } ?>
        </div>
        <div class="woo_discount_loader">
            <div class="lds-ripple"><div></div><div></div></div>
        </div>
    </div>
    <div class="clear"></div>
<?php include_once(WOO_DISCOUNT_DIR . '/view/includes/footer.php'); ?>