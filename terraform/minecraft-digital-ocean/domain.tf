# You can also update your DNS record assuming you are using Digital Ocean
# for handling DNS. Change the entries here then uncomment this section
#resource "digitalocean_record" "minecraft-domain-com" {
#    domain = "domain.com"
#    type = "A"
#    name = "minecraft"
#    value = "${digitalocean_droplet.minecraft.ipv4_address}"
#}
