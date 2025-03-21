output "virtual_network_name" {
    value = azurerm_virtual_network.default.name
}

output "azurerm_subnet_id" {
    value = azurerm_subnet.default.id
}

output "private_dns_zone_id" {
    value = azurerm_private_dns_zone.default.id
}

output "azurerm_private_dns_zone_virtual_network_link" {
    value = azurerm_private_dns_zone_virtual_network_link.default
}

output "network_security_group_id" {
    value = azurerm_network_security_group.default.id
}
