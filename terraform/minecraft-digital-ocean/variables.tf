# API token
variable "do_token" {}

# Location of the private key
variable "pvt_key" {}

# Location of the public key (it will be uploaded
variable "pub_key" {}

# Default image name to use
variable "do_image_name" {
    default = "ubuntu-14-04-x64"
}

# Default image name to use
variable "do_default_size" {
    default = "2gb"
}

# There to launch the instance
variable "do_default_region" {
    default = "nyc3"
}


# Where to download the Minecraft server JAR
variable "minecraft_server_url" {
    default = "https://s3.amazonaws.com/Minecraft.Download/versions/1.9.2/minecraft_server.1.9.2.jar"
}