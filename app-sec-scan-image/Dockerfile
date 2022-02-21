FROM shiftleft/sast-scan:latest

WORKDIR /opt
# Copy necessary scripts
COPY . /
# Set timezone to Europe
ENV TZ=Madrid/Europe
# Run install script to install all tools
RUN chmod +x /usr/local/*.sh && /usr/local/install.sh
# Spawn pid 1 & entrypoint script
ENTRYPOINT ["/bin/tini", "--"]
CMD ["/bin/sh", "-c", "/usr/local/entrypoint.sh"]


