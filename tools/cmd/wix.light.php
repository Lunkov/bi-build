<?

//  "$(WIX_ROOT)/light.exe" -spdb -loc $(INSTALL_DIR)/$(LOC_FILE) -ext WixUIExtension -ext WixDifxAppExtension -ext WixUtilExtension -cultures:$(CULTURES) -b $(INSTALL_DIR) -nologo -out $(1) $(WIX_ROOT)/$(DIFXAPP_LIB) $(2)