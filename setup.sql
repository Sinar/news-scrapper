CREATE TABLE IF NOT EXISTS pages (
    id int NOT NULL AUTO_INCREMENT,
    insertion_time INT NOT NULL,
    site VARCHAR(1024),
    link VARCHAR(1024),
    title VARCHAR(1024),
    html TEXT,
    extracted_content TEXT,
    tags TEXT,
    PRIMARY KEY (id)
)
