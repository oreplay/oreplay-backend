# Manages the Virtual Network
resource "azurerm_virtual_network" "default" {
    name                = "${var.name_prefix}-vnet"
    location            = var.resource_group_location
    resource_group_name = var.resource_group_name
    address_space       = ["10.0.0.0/16"]
}

# Manages the Subnet
resource "azurerm_subnet" "default" {
    name                 = "${var.name_prefix}-subnet"
    resource_group_name  = var.resource_group_name
    virtual_network_name = azurerm_virtual_network.default.name
    address_prefixes     = ["10.0.2.0/24"]
    service_endpoints    = ["Microsoft.Storage"]

    delegation {
        name = "fs"

        service_delegation {
            name = "Microsoft.DBforMySQL/flexibleServers"
            actions = [
                "Microsoft.Network/virtualNetworks/subnets/join/action",
            ]
        }
    }
}

# Enables you to manage Private DNS zones within Azure DNS
resource "azurerm_private_dns_zone" "default" {
    name                = "${var.name_prefix}.mysql.database.azure.com"
    resource_group_name = var.resource_group_name
}

# Enables you to manage Private DNS zone Virtual Network Links
resource "azurerm_private_dns_zone_virtual_network_link" "default" {
    name                  = "${var.name_prefix}mysqlfsVnetZone.com"
    private_dns_zone_name = azurerm_private_dns_zone.default.name
    resource_group_name   = var.resource_group_name
    virtual_network_id    = azurerm_virtual_network.default.id

    depends_on = [azurerm_subnet.default]
}

resource "azurerm_network_security_group" "default" {
    name                = "${var.name_prefix}-nsg"
    location            = var.resource_group_location
    resource_group_name = var.resource_group_name

    security_rule {
        name                       = "allow-ssh"
        priority                   = 110
        direction                  = "Inbound"
        access                     = "Allow"
        protocol                   = "Tcp"
        source_port_range          = "*"
        destination_port_range     = "22"
        source_address_prefix      = "*"
        destination_address_prefix = "*"
    }

    security_rule {
        name                       = "allow-http"
        priority                   = 100
        direction                  = "Inbound"
        access                     = "Allow"
        protocol                   = "Tcp"
        source_port_range          = "*"
        destination_port_range     = "80"
        source_address_prefix      = "*"
        destination_address_prefix = "*"
    }
}
