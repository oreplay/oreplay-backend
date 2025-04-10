## terraform destroy
# terraform init -upgrade
# terraform validate
## export BROWSER="/usr/bin/firefox"
## export ARM_SUBSCRIPTION_ID="61f3117d-ee5e-4acc-80c0-fe234ea241d7"
## export ARM_TENANT_ID="3326c0e7-e6aa-4e04-bbdf-0b325951674c"
## az ad sp create-for-rbac --name terraform-sp --role="Contributor" --scopes="/subscriptions/$ARM_SUBSCRIPTION_ID"
## export ARM_CLIENT_ID="4e33f4b4-95a1-4a69-a230-350e21389abc" # appId
## export ARM_CLIENT_SECRET="xxxxx~_xxxxxxxxxxxxxxxxxxx-xxx.xxxx-xxxx" # password
# terraform plan -out main.tfplan
# terraform apply main.tfplan
## export TF_LOG=WARN # TRACE, DEBUG, INFO, WARN or ERROR
## sudo cloud-init status --long
## ssh vm_admin_user@www.oreplay.es
resource "azurerm_resource_group" "rg" {
    location = var.resource_group_location
    name     = "${var.name_prefix}-resource-group"
}

## Generate random value for the name
#resource "random_string" "name" {
#    length  = 8
#    lower   = true
#    numeric = false
#    special = false
#    upper   = false
#}

module "network" {
    source = "./network"
    resource_group_location = var.resource_group_location
    resource_group_name = azurerm_resource_group.rg.name
    name_prefix = var.name_prefix
}

module "vm" {
    source               = "./vm"
    resource_group_location = var.resource_group_location
    resource_group_name = azurerm_resource_group.rg.name
    network_security_group_id = module.network.network_security_group_id
    virtual_network_name = module.network.virtual_network_name
    name_prefix = var.name_prefix
    ssh_pub_full_path = var.ssh_pub_full_path
}

module "mysql" {
    source               = "./mysql"
    depends_on = [module.network.azurerm_private_dns_zone_virtual_network_link]
    mysql_admin_password = var.admin_password
    delegated_subnet_id = module.network.azurerm_subnet_id # azurerm_subnet.default.id
    private_dns_zone_id = module.network.private_dns_zone_id # azurerm_private_dns_zone.default.id
    resource_group_location = var.resource_group_location
    resource_group_name = azurerm_resource_group.rg.name
    name_prefix = var.name_prefix
}
