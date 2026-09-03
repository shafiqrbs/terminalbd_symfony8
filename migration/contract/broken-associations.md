# Pre-existing broken bidirectional associations

Generated from next/bin/validate-mapping.php. Each entry is an inversedBy/mappedBy
pointing at a field that does not exist on the other entity. These are NOT port
artifacts - the same mappings are byte-identical in the SF2.8 tree, and Doctrine 2
never resolved them because the inverse side was only touched lazily.

Fix each when its module is ported: either add the missing inverse field or
make the association unidirectional.

App\HumanResource\Entity\PayrollSheetParticular
  - The association App\HumanResource\Entity\PayrollSheetParticular#payrollSheet refers to the inverse side field App\HumanResource\Entity\PayrollSheet#payrollSheetParticulars which does not exist.
  - The association App\HumanResource\Entity\PayrollSheetParticular#employee refers to the inverse side field App\HumanResource\Entity\EmployeePayroll#payrollSheetParticulars which does not exist.
  - The association App\HumanResource\Entity\PayrollSheetParticular#particular refers to the inverse side field App\HumanResource\Entity\PayrollSetting#payrollSheetParticular which does not exist.
App\Inventory\Entity\ColorSize
  - The mappings App\Inventory\Entity\ColorSize#itemColor and App\Inventory\Entity\Item#color are inconsistent with each other.
  - The mappings App\Inventory\Entity\ColorSize#itemSize and App\Inventory\Entity\Item#size are inconsistent with each other.
App\Inventory\Entity\SalesTemporary
  - The association App\Inventory\Entity\SalesTemporary#inventoryConfig refers to the inverse side field App\Inventory\Entity\InventoryConfig#serviceSales which does not exist.
  - The association App\Inventory\Entity\SalesTemporary#purchaseItem refers to the inverse side field App\Inventory\Entity\PurchaseItem#medicineSalesTemporary which does not exist.
App\Inventory\Entity\ItemModel
  - The association App\Inventory\Entity\ItemModel#inventoryConfig refers to the inverse side field App\Inventory\Entity\InventoryConfig#model which does not exist.
  - The association App\Inventory\Entity\ItemModel#item refers to the owning side field App\Inventory\Entity\Item#model which is not defined as association, but as field.
  - The association App\Inventory\Entity\ItemModel#item refers to the owning side field App\Inventory\Entity\Item#model which does not exist.
  - The association App\Inventory\Entity\ItemModel#purchaseVendorItems refers to the owning side field App\Inventory\Entity\PurchaseVendorItem#model which does not exist.
  - The association App\Inventory\Entity\ItemModel#goodsItems refers to the owning side field App\Inventory\Entity\GoodsItem#model which does not exist.
App\Inventory\Entity\ItemInventory
  - The association App\Inventory\Entity\ItemInventory#item refers to the inverse side field App\Inventory\Entity\Item#itemInventory which does not exist.
App\Business\Entity\BusinessStockHistory
  - The association App\Business\Entity\BusinessStockHistory#item refers to the inverse side field App\Business\Entity\BusinessParticular#stockHistory which does not exist.
App\Business\Entity\ItemImport
  - The association App\Business\Entity\ItemImport#businessConfig refers to the inverse side field App\Business\Entity\BusinessConfig#itemImports which does not exist.
App\Business\Entity\Courier
  - The association App\Business\Entity\Courier#invoices refers to the owning side field App\Business\Entity\BusinessInvoice#courier which does not exist.
App\Business\Entity\BusinessDistributionReturnItem
  - The association App\Business\Entity\BusinessDistributionReturnItem#businessParticular refers to the inverse side field App\Business\Entity\BusinessParticular#businessDistributionReturnItem which does not exist.
App\Medicine\Entity\MedicineTransfer
  - The association App\Medicine\Entity\MedicineTransfer#medicineConfig refers to the inverse side field App\Medicine\Entity\MedicineConfig#medicineTransfers which does not exist.
  - The association App\Medicine\Entity\MedicineTransfer#customer refers to the inverse side field App\DomainUser\Entity\Customer#medicineTransfers which does not exist.
  - The association App\Medicine\Entity\MedicineTransfer#approvedBy refers to the inverse side field App\User\Entity\User#medicineTransfersReturnApprovedBy which does not exist.
App\Medicine\Entity\MedicineStockAdjustment
  - The association App\Medicine\Entity\MedicineStockAdjustment#medicineConfig refers to the inverse side field App\Medicine\Entity\MedicineConfig#stockAdjustment which does not exist.
  - The association App\Medicine\Entity\MedicineStockAdjustment#medicineStock refers to the inverse side field App\Medicine\Entity\MedicineStock#stockAdjustment which does not exist.
  - The association App\Medicine\Entity\MedicineStockAdjustment#adjustment refers to the inverse side field App\Medicine\Entity\MedicineParticular#stockAdjustment which does not exist.
App\Medicine\Entity\MedicineTransferItem
  - The association App\Medicine\Entity\MedicineTransferItem#medicineStock refers to the inverse side field App\Medicine\Entity\MedicineStock#medicineTransferItems which does not exist.
App\Medicine\Entity\MedicineDoctorPrescribe
  - The association App\Medicine\Entity\MedicineDoctorPrescribe#medicine refers to the inverse side field App\Medicine\Entity\MedicineBrand#invoiceMedicine which does not exist.
App\User\Entity\User
  - The association App\User\Entity\User#pages refers to the owning side field App\Content\Entity\Page#user which does not exist.
  - The association App\User\Entity\User#homeSliders refers to the owning side field App\Content\Entity\HomeSlider#user which does not exist.
App\Tool\Entity\ApplicationPricing
  - The association App\Tool\Entity\ApplicationPricing#appModule refers to the inverse side field App\Tool\Entity\AppModule#applicationPricing which does not exist.
  - The association App\Tool\Entity\ApplicationPricing#syndicateModule refers to the inverse side field App\Tool\Entity\SyndicateModule#applicationPricing which does not exist.
App\Tool\Entity\SmsBulk
  - The association App\Tool\Entity\SmsBulk#globalOption refers to the inverse side field App\Tool\Entity\GlobalOption#smsmBulk which does not exist.
  - The association App\Tool\Entity\SmsBulk#location refers to the inverse side field App\Location\Entity\Location#smsmBulk which does not exist.
  - The association App\Tool\Entity\SmsBulk#createdBy refers to the inverse side field App\User\Entity\User#smsmBulk which does not exist.
App\Restaurant\Entity\RestaurantStockHistory
  - The association App\Restaurant\Entity\RestaurantStockHistory#item refers to the inverse side field App\Restaurant\Entity\Particular#stockHistory which does not exist.
  - The association App\Restaurant\Entity\RestaurantStockHistory#purchaseItem refers to the inverse side field App\Restaurant\Entity\PurchaseItem#stockHistory which does not exist.
  - The association App\Restaurant\Entity\RestaurantStockHistory#salesItem refers to the inverse side field App\Restaurant\Entity\InvoiceParticular#stockHistory which does not exist.
App\Restaurant\Entity\ProductionBatch
  - The association App\Restaurant\Entity\ProductionBatch#restaurantConfig refers to the inverse side field App\Restaurant\Entity\RestaurantConfig#productionBatch which does not exist.
App\Restaurant\Entity\ProductionExpense
  - The association App\Restaurant\Entity\ProductionExpense#productionItem refers to the inverse side field App\Restaurant\Entity\Particular#productionExpense which does not exist.
  - The association App\Restaurant\Entity\ProductionExpense#productionBatch refers to the inverse side field App\Restaurant\Entity\ProductionBatch#productionExpense which does not exist.
  - The association App\Restaurant\Entity\ProductionExpense#salesItem refers to the inverse side field App\Restaurant\Entity\InvoiceParticular#productionExpense which does not exist.
  - The association App\Restaurant\Entity\ProductionExpense#productionElement refers to the inverse side field App\Restaurant\Entity\ProductionElement#productionExpenseItem which does not exist.
  - The association App\Restaurant\Entity\ProductionExpense#particular refers to the inverse side field App\Restaurant\Entity\Particular#productionExpenseItem which does not exist.
App\Restaurant\Entity\ItemImport
  - The association App\Restaurant\Entity\ItemImport#restaurantConfig refers to the inverse side field App\Restaurant\Entity\RestaurantConfig#itemImports which does not exist.
App\Restaurant\Entity\RestaurantTableInvoice
  - The association App\Restaurant\Entity\RestaurantTableInvoice#table refers to the inverse side field App\Restaurant\Entity\Particular#restaurantTemps which does not exist.
App\Hospital\Entity\HmsAdmissionPatientRelease
  - The association App\Hospital\Entity\HmsAdmissionPatientRelease#admission refers to the inverse side field App\Hospital\Entity\Invoice#admissionInvestigations which does not exist.
  - The association App\Hospital\Entity\HmsAdmissionPatientRelease#doctor refers to the inverse side field App\Hospital\Entity\Particular#ipdVisits which does not exist.
App\Hospital\Entity\HmsAdmissionTodo
  - The association App\Hospital\Entity\HmsAdmissionTodo#admission refers to the inverse side field App\Hospital\Entity\Invoice#admissionInvestigations which does not exist.
  - The association App\Hospital\Entity\HmsAdmissionTodo#doctor refers to the inverse side field App\Hospital\Entity\Particular#ipdVisits which does not exist.
App\Hospital\Entity\DoctorInvoiceParticular
  - The association App\Hospital\Entity\DoctorInvoiceParticular#doctorInvoice refers to the inverse side field App\Hospital\Entity\DoctorInvoice#doctorInvoiceParticulars which does not exist.
App\Hospital\Entity\HmsAdmissionTodoParticular
  - The association App\Hospital\Entity\HmsAdmissionTodoParticular#admission refers to the inverse side field App\Hospital\Entity\Invoice#medications which does not exist.
  - The association App\Hospital\Entity\HmsAdmissionTodoParticular#medicine refers to the inverse side field App\Medicine\Entity\MedicineBrand#invoiceMedicine which does not exist.
