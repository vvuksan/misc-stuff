# We need to publish our SSH key so that we can use it when creating the instance
resource "digitalocean_ssh_key" "default" {
    name = "SSH Key"
    public_key = "${file("${var.pub_key}")}"
}

# Create a new Minecraft droplet
resource "digitalocean_droplet" "minecraft" {
    image = "${var.do_image_name}"
    name = "minecraft"
    region = "${var.do_default_region}"
    size = "${var.do_default_size}"
    ssh_keys = [ "${digitalocean_ssh_key.default.id}" ]

    connection {
      user = "root"
      type = "ssh"
      key_file = "${var.pvt_key}"
      timeout = "2m"
    }
    
    # SSH into it and download
    provisioner "remote-exec" {
        inline = [
        "export PATH=$PATH:/usr/bin",
        "install -o nobody -d /minecraft",
        "wget -O /minecraft/minecraft_server.jar ${var.minecraft_server_url}",
        "sudo apt-get update",
        "apt-get install -y openjdk-7-jre-headless supervisor",
        "echo eula=true > /minecraft/eula.txt",
        "echo '[program:minecraft]\nuser=nobody\ndirectory=/minecraft\ncommand=/usr/bin/java -jar /minecraft/minecraft_server.jar\nautostart=true\nautorestart=true\numask=002\npriority=2\nstartretries=3\nstopwaitsecs=10\nstdout_logfile=/minecraft/minecraft.log\nstdout_logfile_maxbytes=0\nstderr_logfile=/minecraft/minecraft_errors.log\nstderr_logfile_maxbytes=0' > /etc/supervisor/conf.d/minecraft.conf",
        "service supervisor restart"
        ]
    }


}

