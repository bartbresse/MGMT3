
-- -----------------------------------------------------
-- Table `mydb`.`language`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`language` (
  `idLanguage` INT NOT NULL AUTO_INCREMENT,
  `Dutch` VARCHAR(245) NULL,
  `English` VARCHAR(245) NULL,
  `German` VARCHAR(245) NULL,
  PRIMARY KEY (`idLanguage`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `mydb`.`entity`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`entity` (
  `idEntity` INT NOT NULL AUTO_INCREMENT,
  `Name` VARCHAR(245) NULL,
  `Structuurtable_idStructuurtable` INT(11) NULL,
  PRIMARY KEY (`idEntity`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `mydb`.`frontendview`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`frontendview` (
  `idFrontendview` INT NOT NULL AUTO_INCREMENT,
  `Name` VARCHAR(245) NULL,
  PRIMARY KEY (`idFrontendview`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `mydb`.`language_has_entity`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`language_has_entity` (
  `Language_idLanguage` INT NOT NULL,
  `Entity_idEntity` INT NOT NULL,
  PRIMARY KEY (`Language_idLanguage`, `Entity_idEntity`),
  INDEX `fk_language_has_entity_entity1_idx` (`Entity_idEntity` ASC),
  INDEX `fk_language_has_entity_language1_idx` (`Language_idLanguage` ASC),
  CONSTRAINT `fk_language_has_entity_language1`
    FOREIGN KEY (`Language_idLanguage`)
    REFERENCES `mydb`.`language` (`idLanguage`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_language_has_entity_entity1`
    FOREIGN KEY (`Entity_idEntity`)
    REFERENCES `mydb`.`entity` (`idEntity`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `mydb`.`language_has_frontendview`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`language_has_frontendview` (
  `Language_idLanguage` INT NOT NULL,
  `Frontendview_idFrontendview` INT NOT NULL,
  PRIMARY KEY (`Language_idLanguage`, `Frontendview_idFrontendview`),
  INDEX `fk_language_has_frontendview_frontendview1_idx` (`Frontendview_idFrontendview` ASC),
  INDEX `fk_language_has_frontendview_language1_idx` (`Language_idLanguage` ASC),
  CONSTRAINT `fk_language_has_frontendview_language1`
    FOREIGN KEY (`Language_idLanguage`)
    REFERENCES `mydb`.`language` (`idLanguage`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_language_has_frontendview_frontendview1`
    FOREIGN KEY (`Frontendview_idFrontendview`)
    REFERENCES `mydb`.`frontendview` (`idFrontendview`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;
