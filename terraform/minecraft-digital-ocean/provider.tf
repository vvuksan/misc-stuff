# Initialize the provider
provider "digitalocean" {
    token = "${var.do_token}"
}