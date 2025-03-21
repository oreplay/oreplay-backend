variable "name_prefix" {
    description = "For example: o-replay2024"
    type = string
}

variable "admin_password" {
    description = "Define a strong password with uppercase, lowercase and numbers."
    type = string
}

variable "ssh_pub_full_path" {
    description = "Full path to the ssh pub file. E.g: ~/.ssh/id_rsa_azure_oreplay.pub"
    default = "~/.ssh/id_rsa_azure_oreplay.pub"
    type = string
}

variable "resource_group_location" {
    type        = string
    default     = "spaincentral"
    description = "Location of the resource group."
}
