# Colliding route names

Symfony keeps the LAST route defined with a given name and silently drops the
others. Symfony 2 behaved the same way, so these collisions are pre-existing:
the legacy tree has 145 of them and the ported tree 147 (the two extra are
import-group keys in legacy.yaml, not routes).

Consequence worth knowing: /android-api-portal/apps is shadowed by the
identically named route in apiPoskeeper.yml, so it is already unreachable in
production - one of the 96 "wire-locked" mobile endpoints is dead today.

147 colliding names:

  _www_main_domain                         legacy/Domain/routing.yml  |  legacy/Domain/routing-backup.yml
  accounthead                              legacy/Tool/routing/accounthead.yml  |  legacy/Accounting/routing/accounthead.yml
  accounthead_create                       legacy/Tool/routing/accounthead.yml  |  legacy/Accounting/routing/accounthead.yml
  accounthead_delete                       legacy/Tool/routing/accounthead.yml  |  legacy/Accounting/routing/accounthead.yml
  accounthead_edit                         legacy/Tool/routing/accounthead.yml  |  legacy/Accounting/routing/accounthead.yml
  accounthead_new                          legacy/Tool/routing/accounthead.yml  |  legacy/Accounting/routing/accounthead.yml
  accounthead_show                         legacy/Tool/routing/accounthead.yml  |  legacy/Accounting/routing/accounthead.yml
  accounthead_update                       legacy/Tool/routing/accounthead.yml  |  legacy/Accounting/routing/accounthead.yml
  agent_create                             legacy/HumanResource/routing/hrreport.yml  |  legacy/DomainUser/routing/agent.yml
  agent_delete                             legacy/HumanResource/routing/hrreport.yml  |  legacy/DomainUser/routing/agent.yml
  agent_edit                               legacy/HumanResource/routing/hrreport.yml  |  legacy/DomainUser/routing/agent.yml
  agent_name                               legacy/HumanResource/routing/hrreport.yml  |  legacy/DomainUser/routing/agent.yml
  agent_new                                legacy/HumanResource/routing/hrreport.yml  |  legacy/DomainUser/routing/agent.yml
  agent_search                             legacy/HumanResource/routing/hrreport.yml  |  legacy/DomainUser/routing/agent.yml
  agent_show                               legacy/HumanResource/routing/hrreport.yml  |  legacy/DomainUser/routing/agent.yml
  agent_update                             legacy/HumanResource/routing/hrreport.yml  |  legacy/DomainUser/routing/agent.yml
  api_portal_app                           legacy/Bindu/apiPoskeeper.yml  |  legacy/Bindu/apiPortal.yml
  bindu_build                              legacy/Bindu/routing/widget.yml  |  legacy/Bindu/routing/build.yml
  business_area                            legacy/Business/routing.yml  |  legacy/Business/routing/area.yml
  business_brand                           legacy/Business/routing.yml  |  legacy/Business/routing/brand.yml
  business_category                        legacy/Business/routing.yml  |  legacy/Business/routing/category.yml
  business_courier                         legacy/Business/routing.yml  |  legacy/Business/routing/courier.yml
  business_damage                          legacy/Business/routing.yml  |  legacy/Business/routing/damage.yml
  business_distribution_return             legacy/Business/routing.yml  |  legacy/Business/routing/distributionreturn.yml
  business_invoice                         legacy/Business/routing.yml  |  legacy/Business/routing/invoice.yml
  business_invoice_return                  legacy/Business/routing.yml  |  legacy/Business/routing/invoicereturn.yml
  business_marketing                       legacy/Business/routing.yml  |  legacy/Business/routing/marketing.yml
  business_product                         legacy/Business/routing.yml  |  legacy/Business/routing/product.yml
  business_purchase                        legacy/Business/routing.yml  |  legacy/Business/routing/purchase.yml
  business_purchase_return                 legacy/Business/routing.yml  |  legacy/Business/routing/purchasereturn.yml
  business_reverse                         legacy/Business/routing.yml  |  legacy/Business/routing/reverse.yml
  business_sales_user                      legacy/Business/routing.yml  |  legacy/Business/routing/salesuser.yml
  business_stock                           legacy/Business/routing.yml  |  legacy/Business/routing/stock.yml
  business_stock_adjustment                legacy/Business/routing.yml  |  legacy/Business/routing/adjustment.yml
  business_stock_transfer                  legacy/Business/routing.yml  |  legacy/Business/routing/stock.yml  |  legacy/Business/routing/stock_transfer.yml
  business_store                           legacy/Business/routing.yml  |  legacy/Business/routing/store.yml
  business_store_ledger                    legacy/Business/routing.yml  |  legacy/Business/routing/storeLedger.yml
  business_vendor                          legacy/Business/routing.yml  |  legacy/Business/routing/vendor.yml
  business_vendor_stock                    legacy/Business/routing.yml  |  legacy/Business/routing/vendorstock.yml
  business_wearhouse                       legacy/Business/routing.yml  |  legacy/Business/routing/wearhouse.yml
  customer                                 legacy.yaml  |  legacy/User/routing.yml
  customer_domain                          legacy/Customer/routing.yml  |  legacy/Customer/routing/domain.yml
  customer_self_create                     legacy/Customer/routing/business.yml  |  legacy/Customer/routing/customer.yml
  customer_self_new                        legacy/Customer/routing/business.yml  |  legacy/Customer/routing/customer.yml
  customerweb_report_cashoverview          legacy/Customer/routing/customerinvoice.yml  |  legacy/Customer/routing/customerweb.yml
  customerweb_report_ledger                legacy/Customer/routing/customerinvoice.yml  |  legacy/Customer/routing/customerweb.yml
  domain_association_member_show           legacy/DomainUser/routing/association.yml  |  legacy/DomainUser/routing/customer.yml
  domain_employee_branch                   legacy/DomainUser/routing.yml  |  legacy/DomainUser/routing/employee_branch.yml
  domain_location_name                     legacy/HumanResource/routing/hrreport.yml  |  legacy/HumanResource/routing/leave.yml  |  legacy/DomainUser/routing/association.yml  |  legacy/DomainUser/routing/agent.yml  |  legacy/DomainUser/routing/customer.yml
  domain_location_search                   legacy/HumanResource/routing/hrreport.yml  |  legacy/HumanResource/routing/leave.yml  |  legacy/DomainUser/routing/association.yml  |  legacy/DomainUser/routing/agent.yml  |  legacy/DomainUser/routing/customer.yml
  domain_store                             legacy/DomainUser/routing.yml  |  legacy/DomainUser/routing/store.yml
  domain_user                              legacy.yaml  |  legacy/DomainUser/routing/domainuser.yml
  dps_diseases                             legacy/DoctorPrescription/routing.yml  |  legacy/DoctorPrescription/routing/diseases.yml
  dps_doctor                               legacy/DoctorPrescription/routing.yml  |  legacy/DoctorPrescription/routing/doctor.yml
  dps_expensecategory                      legacy/DoctorPrescription/routing.yml  |  legacy/DoctorPrescription/routing/expensecategory.yml
  dps_invoice                              legacy/DoctorPrescription/routing.yml  |  legacy/DoctorPrescription/routing/invoice.yml
  dps_particular                           legacy/DoctorPrescription/routing.yml  |  legacy/DoctorPrescription/routing/particular.yml
  dps_prescription                         legacy/DoctorPrescription/routing.yml  |  legacy/DoctorPrescription/routing/prescription.yml
  dps_service                              legacy/DoctorPrescription/routing.yml  |  legacy/DoctorPrescription/routing/service.yml
  dps_treatment                            legacy/DoctorPrescription/routing.yml  |  legacy/DoctorPrescription/routing/treatment.yml
  dps_treatment_accessories                legacy/DoctorPrescription/routing.yml  |  legacy/DoctorPrescription/routing/treatmentaccessories.yml
  dps_treatment_appointment_schedule       legacy/DoctorPrescription/routing/prescription.yml  |  legacy/DoctorPrescription/routing/invoice.yml
  dps_treatment_date_wise_schedule         legacy/DoctorPrescription/routing/prescription.yml  |  legacy/DoctorPrescription/routing/invoice.yml
  dps_treatment_inline_update              legacy/DoctorPrescription/routing/prescription.yml  |  legacy/DoctorPrescription/routing/invoice.yml
  dps_treatment_plan                       legacy/DoctorPrescription/routing/prescription.yml  |  legacy/DoctorPrescription/routing/invoice.yml
  dps_treatment_plan_sms                   legacy/DoctorPrescription/routing/prescription.yml  |  legacy/DoctorPrescription/routing/invoice.yml
  hms_accessories                          legacy/Business/routing/accessories.yml  |  legacy/Hospital/routing/accessories.yml
  hms_accessories_create                   legacy/Business/routing/accessories.yml  |  legacy/Hospital/routing/accessories.yml
  hms_accessories_delete                   legacy/Business/routing/accessories.yml  |  legacy/Hospital/routing/accessories.yml
  hms_accessories_edit                     legacy/Business/routing/accessories.yml  |  legacy/Hospital/routing/accessories.yml
  hms_accessories_inline                   legacy/Business/routing/accessories.yml  |  legacy/Hospital/routing/accessories.yml
  hms_accessories_status                   legacy/Business/routing/accessories.yml  |  legacy/Hospital/routing/accessories.yml
  hms_accessories_update                   legacy/Business/routing/accessories.yml  |  legacy/Hospital/routing/accessories.yml
  hms_customer                             legacy/DomainUser/routing.yml  |  legacy/DomainUser/routing/hms_customer.yml
  hms_invoice_admission                    legacy/Hospital/routing/invoice.yml  |  legacy/Hospital/routing/invoiceadmission.yml
  hms_invoice_particular                   legacy/Hospital/routing/invoiceparticular.yml  |  legacy/DoctorPrescription/routing/invoiceparticular.yml
  hms_invoice_particular_collection        legacy/Hospital/routing/invoiceparticular.yml  |  legacy/DoctorPrescription/routing/invoiceparticular.yml
  hms_invoice_particular_collection_barcode legacy/Hospital/routing/invoiceparticular.yml  |  legacy/DoctorPrescription/routing/invoiceparticular.yml
  hms_invoice_particular_collection_confirm legacy/Hospital/routing/invoiceparticular.yml  |  legacy/DoctorPrescription/routing/invoiceparticular.yml
  hms_invoice_particular_delivery          legacy/Hospital/routing/invoiceparticular.yml  |  legacy/DoctorPrescription/routing/invoiceparticular.yml
  hms_invoice_particular_preparation       legacy/Hospital/routing/invoiceparticular.yml  |  legacy/DoctorPrescription/routing/invoiceparticular.yml
  hms_invoice_particular_report_print      legacy/Hospital/routing/invoiceparticular.yml  |  legacy/DoctorPrescription/routing/invoiceparticular.yml
  hms_invoice_particular_show              legacy/Hospital/routing/invoiceparticular.yml  |  legacy/DoctorPrescription/routing/invoiceparticular.yml
  hms_invoice_particular_update            legacy/Hospital/routing/invoiceparticular.yml  |  legacy/DoctorPrescription/routing/invoiceparticular.yml
  hms_invoice_pathological_report_delete   legacy/Hospital/routing/invoiceparticular.yml  |  legacy/DoctorPrescription/routing/invoiceparticular.yml
  inventory_barcode                        legacy/Inventory/routing.yml  |  legacy/Inventory/routing/barcode.yml
  inventory_category                       legacy/Inventory/routing.yml  |  legacy/Inventory/routing/category.yml
  inventory_customer                       legacy/DomainUser/routing.yml  |  legacy/DomainUser/routing/customerInventory.yml
  inventory_damage                         legacy/Inventory/routing.yml  |  legacy/Inventory/routing/damage.yml
  inventory_delivery                       legacy/Inventory/routing.yml  |  legacy/Inventory/routing/delivery.yml
  inventory_excelimproter                  legacy/Inventory/routing.yml  |  legacy/Inventory/routing/excelimporter.yml
  inventory_goods                          legacy/Inventory/routing.yml  |  legacy/Inventory/routing/goods.yml
  inventory_grn_name                       legacy/Inventory/routing/purchase.yml  |  legacy/Inventory/routing/purchasesimple.yml
  inventory_grn_search                     legacy/Inventory/routing/purchase.yml  |  legacy/Inventory/routing/purchasesimple.yml
  inventory_item                           legacy/Inventory/routing.yml  |  legacy/Inventory/routing/item.yml
  inventory_purchaseitem                   legacy/Inventory/routing.yml  |  legacy/Inventory/routing/purchaseitem.yml
  inventory_purchasereturn                 legacy/Inventory/routing.yml  |  legacy/Inventory/routing/purchasereturn.yml
  inventory_purchasesimple                 legacy/Inventory/routing.yml  |  legacy/Inventory/routing/purchasesimple.yml
  inventory_purchasevendoritem             legacy/Inventory/routing.yml  |  legacy/Inventory/routing/purchasevendoritem.yml
  inventory_sales                          legacy/Inventory/routing.yml  |  legacy/Inventory/routing/sales.yml
  inventory_sales_reset                    legacy/Inventory/routing.yml  |  legacy/Inventory/routing/sales.yml
  inventory_sales_user                     legacy/Inventory/routing.yml  |  legacy/Inventory/routing/salesuser.yml
  inventory_salesgeneral                   legacy/Inventory/routing.yml  |  legacy/Inventory/routing/salesgeneral.yml
  inventory_salesimport                    legacy/Inventory/routing.yml  |  legacy/Inventory/routing/salesimport.yml
  inventory_salesreturn                    legacy/Inventory/routing.yml  |  legacy/Inventory/routing/salesreturn.yml
  inventory_stockitem                      legacy/Inventory/routing.yml  |  legacy/Inventory/routing/stockitem.yml
  inventory_vendor                         legacy/Inventory/routing.yml  |  legacy/Inventory/routing/vendor.yml
  inventory_vendoritem_gallery             legacy/Inventory/routing/foodproduct.yml  |  legacy/Inventory/routing/goods.yml  |  legacy/Inventory/routing/purchasevendoritem.yml
  inventory_vendoritem_inline_update       legacy/Inventory/routing/foodproduct.yml  |  legacy/Inventory/routing/purchasevendoritem.yml
  inventory_vendoritem_status              legacy/Inventory/routing/foodproduct.yml  |  legacy/Inventory/routing/purchasevendoritem.yml
  inventory_warehouse                      legacy/Inventory/routing.yml  |  legacy/Inventory/routing/warehouse.yml
  medicine                                 legacy.yaml  |  legacy/Medicine/routing/medicine.yml
  medicine_import                          legacy/Medicine/routing.yml  |  legacy/Tool/routing/medicineimport.yml
  medicine_user                            legacy/Medicine/routing/medicineuser.yml  |  legacy/Medicine/routing/medicine.yml
  member_profile                           legacy/Customer/routing.yml  |  legacy/Customer/routing/member.yml
  mobilebankaccount                        legacy/Tool/routing/portalmobilebankaccount.yml  |  legacy/Tool/routing/portalbkashaccount.yml
  mobilebankaccount_create                 legacy/Tool/routing/portalmobilebankaccount.yml  |  legacy/Tool/routing/portalbkashaccount.yml
  mobilebankaccount_delete                 legacy/Tool/routing/portalmobilebankaccount.yml  |  legacy/Tool/routing/portalbkashaccount.yml
  mobilebankaccount_edit                   legacy/Tool/routing/portalmobilebankaccount.yml  |  legacy/Tool/routing/portalbkashaccount.yml
  mobilebankaccount_new                    legacy/Tool/routing/portalmobilebankaccount.yml  |  legacy/Tool/routing/portalbkashaccount.yml
  mobilebankaccount_show                   legacy/Tool/routing/portalmobilebankaccount.yml  |  legacy/Tool/routing/portalbkashaccount.yml
  mobilebankaccount_status                 legacy/Tool/routing/portalmobilebankaccount.yml  |  legacy/Tool/routing/portalbkashaccount.yml
  mobilebankaccount_update                 legacy/Tool/routing/portalmobilebankaccount.yml  |  legacy/Tool/routing/portalbkashaccount.yml
  order_delete                             legacy/Customer/routing/product.yml  |  legacy/Customer/routing/order.yml
  order_item_delete                        legacy/Customer/routing/product.yml  |  legacy/Customer/routing/order.yml
  pos_desktop                              legacy/Pos/routing.yml  |  legacy/Pos/routing/pos.yml
  product                                  legacy/Content/routing/product.yml  |  legacy/Product/routing/product.yml
  product_create                           legacy/Content/routing/product.yml  |  legacy/Product/routing/product.yml
  product_delete                           legacy/Content/routing/product.yml  |  legacy/Product/routing/product.yml
  product_edit                             legacy/Content/routing/product.yml  |  legacy/Product/routing/product.yml
  product_new                              legacy/Content/routing/product.yml  |  legacy/Product/routing/product.yml
  product_show                             legacy/Content/routing/product.yml  |  legacy/Product/routing/product.yml
  product_status                           legacy/Content/routing/product.yml  |  legacy/Product/routing/product.yml
  product_update                           legacy/Content/routing/product.yml  |  legacy/Product/routing/product.yml
  purchase_vendoritem_inline_update        legacy/Inventory/routing/purchase.yml  |  legacy/Inventory/routing/purchasevendoritem.yml
  restaurant_category                      legacy/Restaurant/routing.yml  |  legacy/Restaurant/routing/category.yml
  restaurant_customer                      legacy/DomainUser/routing.yml  |  legacy/DomainUser/routing/customerrestaurant.yml
  restaurant_damage                        legacy/Restaurant/routing.yml  |  legacy/Restaurant/routing/damage.yml
  restaurant_invoice                       legacy/Restaurant/routing.yml  |  legacy/Restaurant/routing/invoice.yml
  restaurant_particular                    legacy/Restaurant/routing.yml  |  legacy/Restaurant/routing/particular.yml
  restaurant_product                       legacy/Restaurant/routing.yml  |  legacy/Restaurant/routing/product.yml
  restaurant_production                    legacy/Restaurant/routing.yml  |  legacy/Restaurant/routing/production.yml
  restaurant_purchase                      legacy/Restaurant/routing.yml  |  legacy/Restaurant/routing/purchase.yml
  restaurant_stock                         legacy/Restaurant/routing.yml  |  legacy/Restaurant/routing/stock.yml
  restaurant_vendor                        legacy/Restaurant/routing.yml  |  legacy/Restaurant/routing/vendor.yml
  weekend_update                           legacy/HumanResource/routing/weekend.yml  |  legacy/HumanResource/routing/attendance.yml
  wishlist_to_cart                         legacy/Customer/routing/wishlist.yml  |  legacy/Customer/routing/order.yml
